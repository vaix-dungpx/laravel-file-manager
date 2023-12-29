<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;


/**
 * @property integer $id
 * @property string $name
 * @property string $path
 * @property string $storage_path
 * @property integer $_lft
 * @property integer $_rgt
 * @property integer $parent_id
 * @property boolean $is_folder
 * @property string $mime
 * @property integer $size
 * @property boolean $uploaded_on_cloud
 * @property string $created_at
 * @property string $updated_at
 * @property integer $created_by
 * @property integer $updated_by
 * @property string $deleted_at
 * @property StarredFile[] $starredFiles
 * @property FileShare[] $fileShares
 */

class File extends Model
{
    use HasFactory, HasCreatorAndUpdater, NodeTrait, SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'path',
        'storage_path',
        '_lft',
        '_rgt',
        'parent_id',
        'is_folder',
        'mime',
        'size',
        'uploaded_on_cloud',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_at'
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    public function starred()
    {
        return $this->hasOne(StarredFile::class, 'file_id', 'id')
            ->where('user_id', Auth::id());
    }

    public function owner(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $attributes['created_by'] == Auth::id() ? 'me' : $this->user->name;
            }
        );
    }

    public function isOwnedBy($userId): bool
    {
        return $this->created_by == $userId;
    }

    public function isRoot()
    {
        return $this->parent_id === null;
    }

    public function get_file_size()
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $this->size > 0 ? floor(log($this->size, 1024)) : 0;

        return number_format($this->size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->parent) {
                return;
            }
            $model->path = ( !$model->parent->isRoot() ? $model->parent->path . '/' : '' ) . Str::slug($model->name);
        });

//        static::deleted(function(File $model) {
//            if (!$model->is_folder) {
//                Storage::delete($model->storage_path);
//            }
//        });
    }

    public function moveToTrash()
    {
        $this->deleted_at = Carbon::now();

        return $this->save();
    }

    public function deleteForever()
    {
        $this->deleteFilesFromStorage([$this]);
        $this->forceDelete();
    }

    public function deleteFilesFromStorage($files)
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->deleteFilesFromStorage($file->children);
            } else {
                Storage::delete($file->storage_path);
            }
        }
    }

    public static function getSharedWithMe()
    {
        return File::query()
            ->select('files.*')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->where('file_shares.user_id', Auth::id())
            ->orderBy('file_shares.created_at', 'desc')
            ->orderBy('files.id', 'desc');
    }

    public static function getSharedByMe()
    {
        return File::query()
            ->select('files.*')
            ->join('file_shares', 'file_shares.file_id', 'files.id')
            ->where('files.created_by', Auth::id())
            ->orderBy('file_shares.created_at', 'desc')
            ->orderBy('files.id', 'desc')
            ;
    }

    //Get Previous File Url
    public function getPreviousFileUrl()
    {
        $previousFile = File::query()
            ->where('id', '<', $this->id)
            ->where('parent_id', $this->parent_id)
            ->orderBy('id', 'desc')
            ->first();
        if ($previousFile) {
            return $previousFile->url;
        }
        return null;
    }

    //Calculate Folder Size
    public function calculateFolderSize()
    {
        $size = 0;
        foreach ($this->children as $child) {
            if ($child->is_folder) {
                $size += $child->calculateFolderSize();
            } else {
                $size += $child->size;
            }
        }
        return $size;
    }
}
