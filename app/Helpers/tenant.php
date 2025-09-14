<?php

if (! function_exists('tenant')) {
    /**
     * Ambil tenant yang aktif dari container.
     *
     * @param string|null $key
     * @return mixed
     */
    function tenant($key = null)
    {
        $tenant = app()->has('tenant') ? app('tenant') : null;

        if (! $tenant) {
            return null;
        }

        return $key ? ($tenant->$key ?? null) : $tenant;
    }
}
