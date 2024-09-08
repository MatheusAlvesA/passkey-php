<?php

namespace Matheus\PasskeyPhp\Model;

class User
{
    public int $id;
    public string $username;
    public string $color;

    public function populateFromArray(array $data)
    {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->color = $data['color'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'color' => $this->color,
        ];
    }
}
