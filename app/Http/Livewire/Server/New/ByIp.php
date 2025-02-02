<?php

namespace App\Http\Livewire\Server\New;

use App\Models\Server;
use Livewire\Component;

class ByIp extends Component
{
    public $private_keys;
    public $limit_reached;
    public int|null $private_key_id = null;
    public $new_private_key_name;
    public $new_private_key_description;
    public $new_private_key_value;

    public string $name;
    public string|null $description = null;
    public string $ip;
    public string $user = 'root';
    public int $port = 22;
    public bool $is_part_of_swarm = false;

    protected $rules = [
        'name' => 'required|string',
        'description' => 'nullable|string',
        'ip' => 'required|ip',
        'user' => 'required|string',
        'port' => 'required|integer',
    ];
    protected $validationAttributes = [
        'name' => 'name',
        'description' => 'description',
        'ip' => 'ip',
        'user' => 'user',
        'port' => 'port',
    ];

    public function mount()
    {
        $this->name = generate_random_name();
        $this->private_key_id = $this->private_keys->first()->id;
    }

    public function setPrivateKey(string $private_key_id)
    {
        $this->private_key_id = $private_key_id;
    }

    public function instantSave()
    {
        $this->emit('success', 'Application settings updated!');
    }

    public function submit()
    {
        $this->validate();
        try {
            if (!$this->private_key_id) {
                return $this->emit('error', 'You must select a private key');
            }
            $server = Server::create([
                'name' => $this->name,
                'description' => $this->description,
                'ip' => $this->ip,
                'user' => $this->user,
                'port' => $this->port,
                'team_id' => currentTeam()->id,
                'private_key_id' => $this->private_key_id,
            ]);
            $server->settings->is_part_of_swarm = $this->is_part_of_swarm;
            $server->settings->save();
            return redirect()->route('server.show', $server->uuid);
        } catch (\Exception $e) {
            return general_error_handler(err: $e);
        }
    }
}
