<?php

namespace Docratech\IolCalculator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientIolCalculation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'examination_id',
        'calculation_date',
        'algorithm_used',
        'axial_length_right',
        'axial_length_left',
        'k1_right',
        'k1_left',
        'k2_right',
        'k2_left',
        'acd_right',
        'acd_left',
        'lens_constant',
        'target_refraction',
        'calculated_iol_power_right',
        'calculated_iol_power_left',
        'recommended_lens_model',
        'recommended_lens_manufacturer',
        'calculation_parameters',
        'results_summary',
        'notes',
        'calculated_by',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'calculation_date' => 'date',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'calculation_parameters' => 'array',
        'results_summary' => 'array',
    ];

    /**
     * Get the patient that owns the IOL calculation.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the examination that owns the IOL calculation.
     */
    public function examination(): BelongsTo
    {
        return $this->belongsTo(PatientExamination::class, 'examination_id');
    }

    /**
     * Get the user that performed the calculation.
     */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * Get the user that verified the calculation.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only include verified calculations.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include calculations by algorithm.
     */
    public function scopeByAlgorithm($query, $algorithm)
    {
        return $query->where('algorithm_used', $algorithm);
    }

    /**
     * Scope a query to only include calculations by calculator.
     */
    public function scopeByCalculator($query, $calculatorId)
    {
        return $query->where('calculated_by', $calculatorId);
    }

    /**
     * Get the algorithm label.
     */
    public function getAlgorithmLabelAttribute(): string
    {
        return match($this->algorithm_used) {
            'srk_t' => 'SRK/T',
            'hoffer_q' => 'Hoffer Q',
            'holladay_1' => 'Holladay 1',
            'holladay_2' => 'Holladay 2',
            'haigis' => 'Haigis',
            'barrett' => 'Barrett',
            'olsen' => 'Olsen',
            'kane' => 'Kane',
            default => 'Bilinmeyen'
        };
    }

    /**
     * Get the average IOL power.
     */
    public function getAverageIolPowerAttribute(): ?float
    {
        if ($this->calculated_iol_power_right && $this->calculated_iol_power_left) {
            return ($this->calculated_iol_power_right + $this->calculated_iol_power_left) / 2;
        }

        return $this->calculated_iol_power_right ?? $this->calculated_iol_power_left;
    }

    /**
     * Get the average axial length.
     */
    public function getAverageAxialLengthAttribute(): ?float
    {
        if ($this->axial_length_right && $this->axial_length_left) {
            return ($this->axial_length_right + $this->axial_length_left) / 2;
        }

        return $this->axial_length_right ?? $this->axial_length_left;
    }

    /**
     * Get the average K1.
     */
    public function getAverageK1Attribute(): ?float
    {
        if ($this->k1_right && $this->k1_left) {
            return ($this->k1_right + $this->k1_left) / 2;
        }

        return $this->k1_right ?? $this->k1_left;
    }

    /**
     * Get the average K2.
     */
    public function getAverageK2Attribute(): ?float
    {
        if ($this->k2_right && $this->k2_left) {
            return ($this->k2_right + $this->k2_left) / 2;
        }

        return $this->k2_right ?? $this->k2_left;
    }

    /**
     * Mark calculation as verified.
     */
    public function markAsVerified(int $verifiedBy): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);
    }
}
