<?php

namespace App\Core\lookup;

use Illuminate\Http\Request;

final class LookupParams
{
    public const DEFAULT_PER_PAGE = 20;

    public const ALLOWED_PER_PAGE = [10, 20, 50];

    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $q,
        public readonly ?string $status,
        public readonly array $filters,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);
        $perPage = in_array($perPage, self::ALLOWED_PER_PAGE, true) ? $perPage : self::DEFAULT_PER_PAGE;

        $q = $request->input('q');
        $q = is_string($q) ? trim($q) : null;
        if ($q === '') {
            $q = null;
        }

        $status = $request->input('status');
        $status = is_string($status) ? trim($status) : null;
        if ($status === '') {
            $status = null;
        }

        $filters = [];
        foreach ($request->query() as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'filter_')) {
                continue;
            }
            if (! is_scalar($value) && $value !== null) {
                continue;
            }
            $field = substr($key, 7);
            if ($field === '') {
                continue;
            }
            $filters[$field] = is_string($value) ? trim($value) : $value;
        }

        return new self($page, $perPage, $q, $status, $filters);
    }

    public function filter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }
}
