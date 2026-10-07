<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFeedback extends Model
{
    public const ASPECTS = [
        'functionality' => 'Functionality & requirements',
        'usability' => 'Design & ease of use',
        'reliability' => 'Reliability & performance',
        'communication' => 'Communication & collaboration',
        'handover' => 'Handover & support',
    ];

    protected $table = 'project_feedback';
    protected $primaryKey = 'project_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['project_key', 'ratings', 'comment', 'submitted_by', 'published_at'];

    protected $casts = ['ratings' => 'array', 'published_at' => 'datetime'];

    public function publicData(): array
    {
        return [
            'reviewer' => ['name' => 'Amar Bhagat', 'role' => 'Owner', 'business' => 'AP Malls'],
            'ratings' => collect(self::ASPECTS)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label, 'score' => $this->ratings[$key],
            ])->values()->all(),
            'overall_score' => round(array_sum($this->ratings) / count(self::ASPECTS), 1),
            'max_score' => 5,
            'comment' => $this->comment,
            'published_at' => $this->published_at->toIso8601String(),
        ];
    }
}
