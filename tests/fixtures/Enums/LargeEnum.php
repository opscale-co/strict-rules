<?php

namespace Opscale\Enums;

enum LargeEnum: string
{
    case OPTION_ONE = 'option_one';
    case OPTION_TWO = 'option_two';
    case OPTION_THREE = 'option_three';
    case OPTION_FOUR = 'option_four';
    case OPTION_FIVE = 'option_five';
    case OPTION_SIX = 'option_six';
    case OPTION_SEVEN = 'option_seven';
    case OPTION_EIGHT = 'option_eight';
    case OPTION_NINE = 'option_nine';
    case OPTION_TEN = 'option_ten';
    case OPTION_ELEVEN = 'option_eleven';
    case OPTION_TWELVE = 'option_twelve';
    case OPTION_THIRTEEN = 'option_thirteen';
    case OPTION_FOURTEEN = 'option_fourteen';
    case OPTION_FIFTEEN = 'option_fifteen';
    case OPTION_SIXTEEN = 'option_sixteen';
    case OPTION_SEVENTEEN = 'option_seventeen';
    case OPTION_EIGHTEEN = 'option_eighteen';
    case OPTION_NINETEEN = 'option_nineteen';
    case OPTION_TWENTY = 'option_twenty';

    public function getDisplayName(): string
    {
        return match($this) {
            self::OPTION_ONE => 'First Option',
            self::OPTION_TWO => 'Second Option',
            self::OPTION_THREE => 'Third Option',
            self::OPTION_FOUR => 'Fourth Option',
            self::OPTION_FIVE => 'Fifth Option',
            self::OPTION_SIX => 'Sixth Option',
            self::OPTION_SEVEN => 'Seventh Option',
            self::OPTION_EIGHT => 'Eighth Option',
            self::OPTION_NINE => 'Ninth Option',
            self::OPTION_TEN => 'Tenth Option',
            self::OPTION_ELEVEN => 'Eleventh Option',
            self::OPTION_TWELVE => 'Twelfth Option',
            self::OPTION_THIRTEEN => 'Thirteenth Option',
            self::OPTION_FOURTEEN => 'Fourteenth Option',
            self::OPTION_FIFTEEN => 'Fifteenth Option',
            self::OPTION_SIXTEEN => 'Sixteenth Option',
            self::OPTION_SEVENTEEN => 'Seventeenth Option',
            self::OPTION_EIGHTEEN => 'Eighteenth Option',
            self::OPTION_NINETEEN => 'Nineteenth Option',
            self::OPTION_TWENTY => 'Twentieth Option',
        };
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isEven(): bool
    {
        return in_array($this, [
            self::OPTION_TWO,
            self::OPTION_FOUR,
            self::OPTION_SIX,
            self::OPTION_EIGHT,
            self::OPTION_TEN,
            self::OPTION_TWELVE,
            self::OPTION_FOURTEEN,
            self::OPTION_SIXTEEN,
            self::OPTION_EIGHTEEN,
            self::OPTION_TWENTY,
        ]);
    }

    public function isOdd(): bool
    {
        return !$this->isEven();
    }

    public static function getEvenOptions(): array
    {
        return array_filter(self::cases(), fn($option) => $option->isEven());
    }

    public static function getOddOptions(): array
    {
        return array_filter(self::cases(), fn($option) => $option->isOdd());
    }

    public function getNextOption(): ?self
    {
        $cases = self::cases();
        $currentIndex = array_search($this, $cases);
        
        if ($currentIndex === false || $currentIndex === count($cases) - 1) {
            return null;
        }
        
        return $cases[$currentIndex + 1];
    }

    public function getPreviousOption(): ?self
    {
        $cases = self::cases();
        $currentIndex = array_search($this, $cases);
        
        if ($currentIndex === false || $currentIndex === 0) {
            return null;
        }
        
        return $cases[$currentIndex - 1];
    }
}