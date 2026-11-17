<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\CcpaRequests\Enums\RequestStatus;
use JeffersonGoncalves\CcpaRequests\Enums\RequestType;
use JeffersonGoncalves\CcpaRequests\Events\ConsumerRequestClosed;
use JeffersonGoncalves\CcpaRequests\Events\ConsumerRequestOpened;
use JeffersonGoncalves\CcpaRequests\Events\ConsumerRequestOverdue;
use JeffersonGoncalves\CcpaRequests\Facades\CcpaRequests;
use JeffersonGoncalves\CcpaRequests\Models\ConsumerRequest;
use JeffersonGoncalves\CcpaRequests\Tests\Fixtures\Order;
use JeffersonGoncalves\CcpaRequests\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Mary Smith', 'email' => 'mary@example.com', 'tax_id' => '123']);
    Order::create(['user_id' => $this->user->id, 'shipping_address' => 'Main St 1', 'total' => 100]);
    Order::create(['user_id' => $this->user->id, 'shipping_address' => 'Main St 2', 'total' => 200]);

    $other = User::create(['name' => 'John', 'email' => 'joao@example.com']);
    Order::create(['user_id' => $other->id, 'shipping_address' => 'Main St 3', 'total' => 300]);
});

it('opens a request with the legal deadline', function () {
    Event::fake([ConsumerRequestOpened::class]);
    $this->freezeSecond(); // datetime columns drop microseconds

    $request = CcpaRequests::open(RequestType::Know, requester: ['email' => 'mary@example.com']);

    expect($request->status)->toBe(RequestStatus::Pending)
        ->and($request->due_at->equalTo(now()->addDays(45)))->toBeTrue()
        ->and($request->subject)->toBeNull();

    Event::assertDispatched(ConsumerRequestOpened::class);
});

it('exports only the subject data, keyed by table', function () {
    expect(CcpaRequests::export($this->user))->toBe([
        'users' => [['name' => 'Mary Smith', 'email' => 'mary@example.com', 'tax_id' => '123']],
        'orders' => [['shipping_address' => 'Main St 1'], ['shipping_address' => 'Main St 2']],
    ]);
});

it('refuses to fulfil an unverified request', function () {
    CcpaRequests::fulfil(CcpaRequests::open(RequestType::Know, $this->user));
})->throws(LogicException::class, 'Verify');

it('fulfils access with a JSON export on the private disk', function () {
    Storage::fake('local');
    Event::fake([ConsumerRequestClosed::class]);

    $request = CcpaRequests::open(RequestType::Know);
    CcpaRequests::verify($request, $this->user);
    CcpaRequests::fulfil($request, 'Sent by email');

    $request->refresh();

    expect($request->status)->toBe(RequestStatus::Completed)
        ->and($request->response)->toBe('Sent by email')
        ->and($request->export_path)->toBe("ccpa/requests/request-{$request->id}.json")
        ->and(json_decode(Storage::disk('local')->get($request->export_path), true)['orders'])->toHaveCount(2);

    Event::assertDispatched(ConsumerRequestClosed::class);
});

it('fulfils erasure by anonymizing every record of the subject only', function () {
    $request = CcpaRequests::verify(CcpaRequests::open(RequestType::Delete, $this->user));
    CcpaRequests::fulfil($request);

    $user = $this->user->fresh();

    expect($user->name)->toBe('M*********')
        ->and($user->email)->not->toBe('mary@example.com')
        ->and($user->tax_id)->toBe('123') // Keep: legal retention
        ->and(Order::where('user_id', $user->id)->pluck('shipping_address')->all())->toBe([null, null])
        ->and(Order::whereNotNull('shipping_address')->pluck('shipping_address')->all())->toBe(['Main St 3']);
});

it('leaves manual types to complete()', function () {
    CcpaRequests::fulfil(CcpaRequests::verify(CcpaRequests::open(RequestType::Correct, $this->user)));
})->throws(LogicException::class, 'by hand');

it('rejects with a reason and refuses to reopen', function () {
    $request = CcpaRequests::reject(CcpaRequests::open(RequestType::Delete, $this->user), 'Legal retention');

    expect($request->status)->toBe(RequestStatus::Rejected);

    CcpaRequests::fulfil(CcpaRequests::verify($request));
})->throws(LogicException::class, 'closed');

it('dispatches an event per overdue request', function () {
    Event::fake([ConsumerRequestOverdue::class]);
    CcpaRequests::open(RequestType::Know, $this->user);
    CcpaRequests::complete(CcpaRequests::open(RequestType::Know, $this->user));

    $this->travel(46)->days();
    Artisan::call('ccpa:requests-overdue');

    Event::assertDispatchedTimes(ConsumerRequestOverdue::class, 1);
});

it('extends the deadline once by 45 days, within the first 45 days', function () {
    $this->freezeSecond();
    $request = CcpaRequests::open(RequestType::Know, $this->user);
    $due = $request->due_at->copy();

    CcpaRequests::extend($request, 'Thousands of records across several systems');

    expect($request->fresh()->due_at->equalTo($due->addDays(45)))->toBeTrue()
        ->and($request->fresh()->extension_reason)->toBe('Thousands of records across several systems')
        ->and(fn () => CcpaRequests::extend($request, 'again'))->toThrow(LogicException::class, 'already extended');
});

it('refuses to extend after the first 45 days', function () {
    $request = CcpaRequests::open(RequestType::Know, $this->user);

    $this->travel(46)->days();

    CcpaRequests::extend($request, 'Late');
})->throws(LogicException::class, 'first 45 days');

it('tracks the 10-business-day acknowledgement', function () {
    $this->travelTo(now()->startOfWeek()); // Monday
    $request = CcpaRequests::open(RequestType::Know, $this->user);

    expect($request->acknowledge_due_at->toDateString())->toBe(now()->addWeekdays(10)->toDateString());

    $this->travel(15)->days();
    expect(ConsumerRequest::query()->scopes('acknowledgementOverdue')->count())->toBe(1);

    CcpaRequests::acknowledge($request);
    expect(ConsumerRequest::query()->scopes('acknowledgementOverdue')->count())->toBe(0);
});
