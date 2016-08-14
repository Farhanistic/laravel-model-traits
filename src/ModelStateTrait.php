<?php

namespace Bluora\LaravelModelTraits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait ModelStateTrait
{
    /**
     * Remove soft delete scope.
     *
     * @return void
     */
    public static function bootModelStateTrait()
    {
        static::addGlobalScope(new ModelStateScope());
    }

    public function getStateCreatedAtColumn()
    {
        return 'created_at';
    }

    public function getStateUpdatedAtColumn()
    {
        return 'updated_at';
    }

    public function getStateArchivedAtColumn()
    {
        return 'archived_at';
    }

    public function getStateDeletedAtColumn()
    {
        return 'deleted_at';
    }

    public function getColumnWithTable($column)
    {
        return $this->table.'.'.$column;
    }

    public static $mode_active = '0';
    public static $mode_archived = '1';
    public static $mode_deleted = '2';

    /**
     * Scope a query to only include specific models.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMode($query, $mode = '0')
    {
        $query->withoutGlobalScope(SoftDeletingScope::class);
        switch ($mode) {
            case static::$mode_archived:
                $query = $query->archived();
                break;
            case static::$mode_deleted:
                $query = $query->deleted();
                break;
            case static::$mode_active:
            default:
                $query = $query->active();
                break;
        }

        return $query;
    }

    /**
     * Scope a query to only include active models.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query, $type = true)
    {
        return $query->archived(!$type);
    }

    /**
     * Scope a query to only include archived models.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query, $type = true)
    {
        if (static::getStateDeletedAtColumn()) {
            $query = $query->whereNull(static::getColumnWithTable(static::getStateDeletedAtColumn()));
        }
        if (static::getStateArchivedAtColumn()) {
            if ($type === true) {
                $query = $query->whereNotNull(static::getColumnWithTable(static::getStateArchivedAtColumn()));
            } else {
                $query = $query->whereNull(static::getColumnWithTable(static::getStateArchivedAtColumn()));
            }
        }

        return $query;
    }

    /**
     * Scope a query to only include deleted models.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeleted($query, $type = true)
    {
        if (static::getStateDeletedAtColumn()) {
            if ($type === true) {
                $query = $query->whereNotNull(static::getColumnWithTable(static::getStateDeletedAtColumn()));
            } else {
                $query = $query->whereNull(static::getColumnWithTable(static::getStateDeletedAtColumn()));
            }
        }

        return $query;
    }

    /**
     * Activate this model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function activateModel()
    {
        if (static::getStateArchivedAtColumn()) {
            $this->{static::getStateArchivedAtColumn()} = null;
        }
        if (static::getStateDeletedAtColumn()) {
            $this->{static::getStateDeletedAtColumn()} = null;
        }
        if (static::getStateArchivedAtColumn() || static::getStateDeletedAtColumn()) {
            $this->save();
        }
    }

    /**
     * Archive this model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function archiveModel()
    {
        if (static::getStateArchivedAtColumn()) {
            $this->{static::getStateArchivedAtColumn()} = Carbon::now()->toDateTimeString();
            if (static::getStateDeletedAtColumn()) {
                $this->{static::getStateDeletedAtColumn()} = null;
            }
            $this->save();
        }
    }

    /**
     * Delete this model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function deleteModel()
    {
        if (static::getStateDeletedAtColumn()) {
            $this->{static::getStateDeletedAtColumn()} = Carbon::now()->toDateTimeString();
            $this->save();
        }
    }

    public function removeModel()
    {
        $this->deleteModel();
    }

    /**
     * Is this model active?
     *
     * @return string
     */
    public function getIsActiveAttribute()
    {
        return is_null($this->{static::getStateArchivedAtColumn(false)})
            && is_null($this->{static::getStateDeletedAtColumn(false)});
    }

    /**
     * Is this model archived?
     *
     * @return string
     */
    public function getIsArchivedAttribute()
    {
        return !is_null($this->{static::getStateArchivedAtColumn(false)})
            && is_null($this->{static::getStateDeletedAtColumn(false)});
    }

    /**
     * Is this model deleted?
     *
     * @return string
     */
    public function getIsDeletedAttribute()
    {
        return !is_null($this->{static::getStateDeletedAtColumn(false)});
    }

    /**
     * Is this model removed?
     * Alias for deleted.
     *
     * @return string
     */
    public function getIsRemovedAttribute()
    {
        return $this->is_deleted;
    }

    /**
     * Is this model active?
     *
     * @return string
     */
    public function getStateNameAttribute()
    {
        if ($this->is_active) {
            return 'active';
        } elseif ($this->is_archived) {
            return 'archived';
        } else {
            return 'removed';
        }
    }
}
