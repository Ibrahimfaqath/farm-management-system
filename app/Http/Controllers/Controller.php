<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    public function __construct(Request $request)
    {
        // Routes are loaded by name; middleware handles authorisation.
    }

    protected function farmScope(Request $request): ?int
    {
        $farmId = $request->session()->get('current_farm_id');

        return $farmId === null ? null : (int) $farmId;
    }

    protected function flashSuccess(RedirectResponse $response, string $message): RedirectResponse
    {
        return $response->with('status', $message);
    }

    /**
     * Options helper shared by selects.
     *
     * @param  iterable<int|string, string>  $items
     * @return array<int|string, string>
     */
    protected function options(iterable $items): array
    {
        $options = [];

        foreach ($items as $key => $label) {
            $options[$key] = $label;
        }

        return $options;
    }

    /**
     * Convert an enum class into a value => label map for selects.
     *
     * @param  class-string  $enum
     * @return array<string, string>
     */
    protected function enumOptions(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $options[$case->value] = method_exists($case, 'label') ? $case->label() : $case->name;
        }

        return $options;
    }
}
