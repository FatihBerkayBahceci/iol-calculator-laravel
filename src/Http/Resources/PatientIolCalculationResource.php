<?php

namespace Docratech\IolCalculator\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientIolCalculationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'calculation_date' => $this->calculation_date,
            'algorithm_used' => $this->algorithm_used,
            'algorithm_used_label' => $this->algorithm_used_label,
            'axial_length_right' => $this->axial_length_right,
            'axial_length_left' => $this->axial_length_left,
            'k1_right' => $this->k1_right,
            'k1_left' => $this->k1_left,
            'k2_right' => $this->k2_right,
            'k2_left' => $this->k2_left,
            'acd_right' => $this->acd_right,
            'acd_left' => $this->acd_left,
            'lens_constant' => $this->lens_constant,
            'target_refraction' => $this->target_refraction,
            'calculated_iol_power_right' => $this->calculated_iol_power_right,
            'calculated_iol_power_left' => $this->calculated_iol_power_left,
            'predicted_refraction_right' => $this->predicted_refraction_right,
            'predicted_refraction_left' => $this->predicted_refraction_left,
            'recommended_lens_model' => $this->recommended_lens_model,
            'recommended_lens_manufacturer' => $this->recommended_lens_manufacturer,
            'lens_notes' => $this->lens_notes,
            'is_verified' => $this->is_verified,
            'verification_date' => $this->verification_date,
            'verification_notes' => $this->verification_notes,
            'notes' => $this->notes,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'patient' => [
                'id' => $this->patient->id,
                'patient_number' => $this->patient->patient_number,
                'full_name' => $this->patient->full_name,
            ],
            'examination' => $this->when($this->examination, [
                'id' => $this->examination->id,
                'examination_date' => $this->examination->examination_date,
                'examination_type' => $this->examination->examination_type,
            ]),
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ],
            'verified_by' => $this->when($this->verifiedBy, [
                'id' => $this->verifiedBy->id,
                'name' => $this->verifiedBy->name,
            ]),
            'clinic' => [
                'id' => $this->clinic->id,
                'name' => $this->clinic->name,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
