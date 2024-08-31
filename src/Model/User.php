<?php

namespace Matheus\PasskeyPhp\Model;

class User
{
    public int $id;
    public string $username;
    public string $password;
    public string $color;

    public function populateFromArray(array $data)
    {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->color = $data['color'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
            'color' => $this->color,
        ];
    }
}
