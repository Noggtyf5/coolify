<?php

namespace App\Http\Livewire\Waitlist;

use App\Jobs\SendConfirmationForWaitlistJob;
use App\Models\User;
use App\Models\Waitlist;
use Livewire\Component;

class Index extends Component
{
    public string $email;
    public int $waitingInLine = 0;

    protected $rules = [
        'email' => 'required|email',
    ];
    public function render()
    {
        return view('livewire.waitlist.index')->layout('layouts.simple');
    }
    public function mount()
    {
        $this->waitingInLine = Waitlist::whereVerified(true)->count();
        if (isDev()) {
            $this->email = 'waitlist@example.com';
        }
    }
    public function submit()
    {
        $this->validate();
        try {
            $already_registered = User::whereEmail($this->email)->first();
            if ($already_registered) {
                throw new \Exception('You are already on the waitlist or registered. <br>Please check your email to verify your email address or contact support.');
            }
            $found = Waitlist::where('email', $this->email)->first();
            if ($found) {
                if (!$found->verified) {
                    $this->emit('error', 'You are already on the waitlist. <br>Please check your email to verify your email address.');
                    return;
                }
                $this->emit('error', 'You are already on the waitlist. <br>You will be notified when your turn comes. <br>Thank you.');
                return;
            }
            $waitlist = Waitlist::create([
                'email' => $this->email,
                'type' => 'registration',
            ]);

            $this->emit('success', 'Check your email to verify your email address.');
            dispatch(new SendConfirmationForWaitlistJob($this->email, $waitlist->uuid));
        } catch (\Exception $e) {
            return general_error_handler(err: $e, that: $this);
        }
    }
}
