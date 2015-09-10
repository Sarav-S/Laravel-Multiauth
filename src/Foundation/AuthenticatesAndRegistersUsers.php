<?php

namespace Sarav\Multiauth\Foundation;

trait AuthenticatesAndRegistersUsers
{
    use AuthenticatesUsers, RegistersUsers {
        AuthenticatesUsers::redirectPath insteadof RegistersUsers;
    }
}
