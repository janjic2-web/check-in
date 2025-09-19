<?php

use App\Models\User;

$users = User::all();
foreach ($users as $user) {
    echo $user->id . ' | ' . $user->username . ' | ' . $user->email . ' | ' . $user->role . "\n";
}
