<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OptionGroup extends Model
{
    use HasFactory;

    // Group types
    const TYPE_SINGLE = 'single';
    const TYPE_MULTIPLE = 'multiple';
    const TYPE_TEXT = 'text';
    const TYPE_NUMBER = 'number';
    const TYPE_FILE = 'file';
    const TYPE_COLOR = 'color';

    protected $table = 'option_groups';

    protected $fillable = [
        'name',
        'type',
        'min_selections',
        'max_selections',
        'is_required',
        'sort_order',
        'validation_rules',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];


    public function options(): HasMany
    {
        return $this->hasMany(Option::class)->orderBy('sort_order');
    }
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }


    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    public function configurationItems(): HasMany
    {
        return $this->hasMany(ConfigurationItem::class);
    }


    // Check if this group allows multiple selections

    public function isMultiple(): bool
    {
        return $this->type === self::TYPE_MULTIPLE;
    }


    // Check if this group requires user input (text, number, file, color)

    public function requiresInput(): bool
    {
        return in_array($this->type, [
            self::TYPE_TEXT,
            self::TYPE_NUMBER,
            self::TYPE_FILE,
            self::TYPE_COLOR,
        ]);
    }


    // Check if this group selects from predefined options

    public function hasPredefinedOptions(): bool
    {
        return in_array($this->type, [
            self::TYPE_SINGLE,
            self::TYPE_MULTIPLE,
        ]);
    }


    // Validate a selection against this group's rules

    public function validateSelection(array $selection): array
    {
        $errors = [];
        $rules = $this->validation_rules ?? [];

        // For text input
        if ($this->type === self::TYPE_TEXT) {
            $text = $selection['user_input'] ?? '';

            if (isset($rules['min_length']) && strlen($text) < $rules['min_length']) {
                $errors[] = "{$this->name} must be at least {$rules['min_length']} characters";
            }

            if (isset($rules['max_length']) && strlen($text) > $rules['max_length']) {
                $errors[] = "{$this->name} cannot exceed {$rules['max_length']} characters";
            }

            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $text)) {
                $errors[] = $rules['pattern_message'] ?? "{$this->name} has invalid format";
            }
        }

        // For number input
        if ($this->type === self::TYPE_NUMBER) {
            $value = (float)($selection['user_input'] ?? 0);

            if (isset($rules['min']) && $value < $rules['min']) {
                $errors[] = "{$this->name} must be at least {$rules['min']}";
            }

            if (isset($rules['max']) && $value > $rules['max']) {
                $errors[] = "{$this->name} cannot exceed {$rules['max']}";
            }

            if (isset($rules['step']) && fmod($value, $rules['step']) != 0) {
                $errors[] = "{$this->name} must be in increments of {$rules['step']}";
            }
        }

        // For file input
        if ($this->type === self::TYPE_FILE) {
            if (isset($rules['max_size']) && ($selection['file_size'] ?? 0) > $rules['max_size']) {
                $errors[] = "File size cannot exceed {$rules['max_size']} bytes";
            }

            if (isset($rules['allowed_types']) && !in_array($selection['file_type'] ?? '', $rules['allowed_types'])) {
                $errors[] = "File type not allowed. Allowed: " . implode(', ', $rules['allowed_types']);
            }
        }

        // For color input
        if ($this->type === self::TYPE_COLOR) {
            $color = $selection['user_input'] ?? '';
            $pattern = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

            if (!preg_match($pattern, $color)) {
                $errors[] = "{$this->name} must be a valid hex color code";
            }
        }

        return $errors;
    }


    // Get validation rules as array for Laravel validation

    public function getValidationRulesArray(): array
    {
        $rules = [];

        if ($this->type === self::TYPE_TEXT) {
            $validation = $this->validation_rules ?? [];
            if (isset($validation['min_length'])) {
                $rules[] = "min:{$validation['min_length']}";
            }
            if (isset($validation['max_length'])) {
                $rules[] = "max:{$validation['max_length']}";
            }
        }

        if ($this->type === self::TYPE_NUMBER) {
            $validation = $this->validation_rules ?? [];
            if (isset($validation['min'])) {
                $rules[] = "min:{$validation['min']}";
            }
            if (isset($validation['max'])) {
                $rules[] = "max:{$validation['max']}";
            }
            $rules[] = 'numeric';
        }

        return $rules;
    }

    // Scope a query to only include required groups

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }


    // Scope a query to only include optional groups

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }


    // Scope a query to order by sort order

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }



    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($optionGroup) {
            // Delete all options when group is deleted
            $optionGroup->options()->delete();
        });
    }
}
