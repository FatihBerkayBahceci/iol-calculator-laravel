<?php

namespace Docratech\IolCalculator\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreIolCalculationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Kullanıcı yetkisi controller'da kontrol edilecek
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Temel bilgiler
            'patient_id' => 'required|exists:patients,id',
            'examination_id' => 'nullable|exists:patient_examinations,id',
            'calculation_date' => 'required|date|before_or_equal:today',
            'clinic_id' => 'required|exists:clinics,id',
            'doctor_id' => 'required|exists:users,id',
            
            // Biometri verileri
            'axial_length_right' => 'required|numeric|min:18|max:35',
            'axial_length_left' => 'required|numeric|min:18|max:35',
            'k1_right' => 'required|numeric|min:35|max:50',
            'k1_left' => 'required|numeric|min:35|max:50',
            'k2_right' => 'required|numeric|min:35|max:50',
            'k2_left' => 'required|numeric|min:35|max:50',
            'acd_right' => 'required|numeric|min:2|max:5',
            'acd_left' => 'required|numeric|min:2|max:5',
            
            // Algoritma seçimi
            'algorithm_used' => 'required|in:SRK/T,Hoffer Q,Holladay 1,Holladay 2,Haigis,Barrett Universal II',
            'lens_constant' => 'required|numeric|min:100|max:130',
            
            // Hesaplama sonuçları
            'iol_power_right' => 'nullable|numeric|min:-10|max:40',
            'iol_power_left' => 'nullable|numeric|min:-10|max:40',
            'predicted_refraction_right' => 'nullable|numeric|min:-10|max:10',
            'predicted_refraction_left' => 'nullable|numeric|min:-10|max:10',
            
            // Lens önerisi
            'recommended_lens_model' => 'nullable|string|max:100',
            'recommended_lens_manufacturer' => 'nullable|string|max:100',
            'lens_notes' => 'nullable|string|max:500',
            
            // Doğrulama
            'is_verified' => 'nullable|boolean',
            'verified_by' => 'nullable|exists:users,id',
            'verification_date' => 'nullable|date|before_or_equal:today',
            'verification_notes' => 'nullable|string|max:500',
            
            // Notlar
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,calculated,verified,implemented',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Hasta seçimi zorunludur.',
            'patient_id.exists' => 'Seçilen hasta bulunamadı.',
            'examination_id.exists' => 'Seçilen muayene bulunamadı.',
            'calculation_date.required' => 'Hesaplama tarihi zorunludur.',
            'calculation_date.before_or_equal' => 'Hesaplama tarihi bugünden sonra olamaz.',
            'clinic_id.required' => 'Klinik seçimi zorunludur.',
            'clinic_id.exists' => 'Seçilen klinik bulunamadı.',
            'doctor_id.required' => 'Doktor seçimi zorunludur.',
            'doctor_id.exists' => 'Seçilen doktor bulunamadı.',
            
            // Biometri validasyonları
            'axial_length_right.required' => 'Sağ göz aksiyel uzunluk zorunludur.',
            'axial_length_right.numeric' => 'Sağ göz aksiyel uzunluk sayısal olmalıdır.',
            'axial_length_right.min' => 'Sağ göz aksiyel uzunluk en az 18mm olmalıdır.',
            'axial_length_right.max' => 'Sağ göz aksiyel uzunluk en fazla 35mm olmalıdır.',
            'axial_length_left.required' => 'Sol göz aksiyel uzunluk zorunludur.',
            'axial_length_left.numeric' => 'Sol göz aksiyel uzunluk sayısal olmalıdır.',
            'axial_length_left.min' => 'Sol göz aksiyel uzunluk en az 18mm olmalıdır.',
            'axial_length_left.max' => 'Sol göz aksiyel uzunluk en fazla 35mm olmalıdır.',
            
            'k1_right.required' => 'Sağ göz K1 değeri zorunludur.',
            'k1_right.numeric' => 'Sağ göz K1 değeri sayısal olmalıdır.',
            'k1_right.min' => 'Sağ göz K1 değeri en az 35 olmalıdır.',
            'k1_right.max' => 'Sağ göz K1 değeri en fazla 50 olmalıdır.',
            'k1_left.required' => 'Sol göz K1 değeri zorunludur.',
            'k1_left.numeric' => 'Sol göz K1 değeri sayısal olmalıdır.',
            'k1_left.min' => 'Sol göz K1 değeri en az 35 olmalıdır.',
            'k1_left.max' => 'Sol göz K1 değeri en fazla 50 olmalıdır.',
            
            'k2_right.required' => 'Sağ göz K2 değeri zorunludur.',
            'k2_right.numeric' => 'Sağ göz K2 değeri sayısal olmalıdır.',
            'k2_right.min' => 'Sağ göz K2 değeri en az 35 olmalıdır.',
            'k2_right.max' => 'Sağ göz K2 değeri en fazla 50 olmalıdır.',
            'k2_left.required' => 'Sol göz K2 değeri zorunludur.',
            'k2_left.numeric' => 'Sol göz K2 değeri sayısal olmalıdır.',
            'k2_left.min' => 'Sol göz K2 değeri en az 35 olmalıdır.',
            'k2_left.max' => 'Sol göz K2 değeri en fazla 50 olmalıdır.',
            
            'acd_right.required' => 'Sağ göz ACD değeri zorunludur.',
            'acd_right.numeric' => 'Sağ göz ACD değeri sayısal olmalıdır.',
            'acd_right.min' => 'Sağ göz ACD değeri en az 2mm olmalıdır.',
            'acd_right.max' => 'Sağ göz ACD değeri en fazla 5mm olmalıdır.',
            'acd_left.required' => 'Sol göz ACD değeri zorunludur.',
            'acd_left.numeric' => 'Sol göz ACD değeri sayısal olmalıdır.',
            'acd_left.min' => 'Sol göz ACD değeri en az 2mm olmalıdır.',
            'acd_left.max' => 'Sol göz ACD değeri en fazla 5mm olmalıdır.',
            
            'algorithm_used.required' => 'Algoritma seçimi zorunludur.',
            'algorithm_used.in' => 'Geçerli bir algoritma seçiniz.',
            'lens_constant.required' => 'Lens sabiti zorunludur.',
            'lens_constant.numeric' => 'Lens sabiti sayısal olmalıdır.',
            'lens_constant.min' => 'Lens sabiti en az 100 olmalıdır.',
            'lens_constant.max' => 'Lens sabiti en fazla 130 olmalıdır.',
            
            'verified_by.exists' => 'Doğrulayan kullanıcı bulunamadı.',
            'verification_date.before_or_equal' => 'Doğrulama tarihi bugünden sonra olamaz.',
            'status.in' => 'Geçerli bir durum seçiniz.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'patient_id' => 'hasta',
            'examination_id' => 'muayene',
            'calculation_date' => 'hesaplama tarihi',
            'clinic_id' => 'klinik',
            'doctor_id' => 'doktor',
            'axial_length_right' => 'sağ göz aksiyel uzunluk',
            'axial_length_left' => 'sol göz aksiyel uzunluk',
            'k1_right' => 'sağ göz K1',
            'k1_left' => 'sol göz K1',
            'k2_right' => 'sağ göz K2',
            'k2_left' => 'sol göz K2',
            'acd_right' => 'sağ göz ACD',
            'acd_left' => 'sol göz ACD',
            'algorithm_used' => 'kullanılan algoritma',
            'lens_constant' => 'lens sabiti',
            'iol_power_right' => 'sağ göz IOL gücü',
            'iol_power_left' => 'sol göz IOL gücü',
            'predicted_refraction_right' => 'sağ göz tahmini refraksiyon',
            'predicted_refraction_left' => 'sol göz tahmini refraksiyon',
            'recommended_lens_model' => 'önerilen lens modeli',
            'recommended_lens_manufacturer' => 'önerilen lens üreticisi',
            'verified_by' => 'doğrulayan',
            'verification_date' => 'doğrulama tarihi',
            'status' => 'durum',
        ];
    }
}
