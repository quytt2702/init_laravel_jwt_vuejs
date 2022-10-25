<?php

namespace App\Enums\User;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class UserTypeEnum extends Enum
{
    const OptionOne =   0;
    const OptionTwo =   1;
    const OptionThree = 2;
}
