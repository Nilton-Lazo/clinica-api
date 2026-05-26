<?php

namespace App\Core\grid;

use Illuminate\Http\Request;

final class GridParams
{
    public const DEFAULT_PER_PAGE = 10;

    public const ALLOWED_PER_PAGE = [10, 20, 50];

    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $q,
        public readonly ?string $sort,
        public readonly string $sortDir,
        public readonly array $filters,
    ) {}

    public static function fromLegacy(array $filters, array $allowedSorts = [], ?string $defaultSort = null): self
    {
        return self::fromRequest(Request::create('/', 'GET', $filters), $allowedSorts, $defaultSort);
    }

    public static function fromRequest(Request $request, array $allowedSorts = [], ?string $defaultSort = null): self
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);
        $perPage = in_array($perPage, self::ALLOWED_PER_PAGE, true) ? $perPage : self::DEFAULT_PER_PAGE;

        $q = $request->input('q');
        $q = is_string($q) ? trim($q) : null;
        if ($q === '') {
            $q = null;
        }

        $sort = $request->input('sort');
        $sort = is_string($sort) ? trim($sort) : null;
        if ($sort === '' || ($allowedSorts !== [] && ($sort === null || ! in_array($sort, $allowedSorts, true)))) {
            $sort = $defaultSort;
        }

        $sortDir = strtolower((string) $request->input('sort_dir', 'asc'));
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        $filters = [];

        $status = $request->input('status');
        if (is_string($status) && trim($status) !== '') {
            $filters['status'] = trim($status);
        }

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

        return new self($page, $perPage, $q, $sort, $sortDir, $filters);
    }

    public function status(): ?string
    {
        $status = $this->filters['status'] ?? null;

        return is_string($status) && $status !== '' ? $status : null;
    }

    public function filter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    public function toCacheKey(string $prefix): string
    {
        return sprintf(
            '%s:%d:%d:%s:%s:%s:%s',
            $prefix,
            $this->page,
            $this->perPage,
            $this->q ?? '',
            $this->sort ?? '',
            $this->sortDir,
            json_encode($this->filters, JSON_UNESCAPED_UNICODE) ?: ''
        );
    }
}
