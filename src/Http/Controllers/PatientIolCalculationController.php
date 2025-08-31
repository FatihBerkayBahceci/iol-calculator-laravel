<?php

namespace Docratech\IolCalculator\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Docratech\IolCalculator\Models\Patient;
use Docratech\IolCalculator\Models\PatientIolCalculation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PatientIolCalculationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'algorithm_used' => 'nullable|string',
                'is_verified' => 'nullable|boolean',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = PatientIolCalculation::with(['patient', 'examination', 'calculatedBy', 'verifiedBy'])
                ->where('patient_id', $request->patient_id);

            // Filtreler
            if ($request->has('algorithm_used')) {
                $query->byAlgorithm($request->algorithm_used);
            }

            if ($request->has('is_verified')) {
                if ($request->is_verified) {
                    $query->verified();
                }
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('calculation_date', [$request->date_from, $request->date_to]);
            }

            // Sıralama
            $sortBy = $request->get('sort_by', 'calculation_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Sayfalama
            $perPage = $request->get('per_page', 15);
            $calculations = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $calculations,
                'message' => 'IOL hesaplamaları başarıyla getirildi'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IOL hesaplamaları getirilirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Get patient ID from route
            $patientId = $request->route('patient');
            
            $validator = Validator::make(array_merge($request->all(), ['patient_id' => $patientId]), [
                'patient_id' => 'required|exists:patients,id',
                'examination_id' => 'nullable|exists:patient_examinations,id',
                'calculation_date' => 'nullable|date',
                'algorithm_used' => 'required|in:srk_t,hoffer_q,holladay_1,holladay_2,haigis,barrett,olsen,kane',
                'axial_length_right' => 'nullable|numeric|min:20|max:35',
                'axial_length_left' => 'nullable|numeric|min:20|max:35',
                'k1_right' => 'nullable|numeric|min:30|max:50',
                'k1_left' => 'nullable|numeric|min:30|max:50',
                'k2_right' => 'nullable|numeric|min:30|max:50',
                'k2_left' => 'nullable|numeric|min:30|max:50',
                'acd_right' => 'nullable|numeric|min:2|max:5',
                'acd_left' => 'nullable|numeric|min:2|max:5',
                'lens_constant' => 'nullable|numeric|min:100|max:140',
                'target_refraction' => 'nullable|numeric|min:-10|max:10',
                'recommended_lens_model' => 'nullable|string|max:255',
                'recommended_lens_manufacturer' => 'nullable|string|max:255',
                'calculation_parameters' => 'nullable|array',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası',
                    'errors' => $validator->errors()
                ], 422);
            }

            // IOL hesaplama algoritması
            $calculationData = array_merge($request->all(), ['patient_id' => $patientId]);
            $calculationResults = $this->calculateIOL($calculationData);

            $calculation = PatientIolCalculation::create([
                'patient_id' => $patientId,
                'examination_id' => $request->examination_id,
                'calculation_date' => $request->calculation_date ?? now(),
                'algorithm_used' => $request->algorithm_used,
                'axial_length_right' => $request->axial_length_right,
                'axial_length_left' => $request->axial_length_left,
                'k1_right' => $request->k1_right,
                'k1_left' => $request->k1_left,
                'k2_right' => $request->k2_right,
                'k2_left' => $request->k2_left,
                'acd_right' => $request->acd_right,
                'acd_left' => $request->acd_left,
                'lens_constant' => $request->lens_constant,
                'target_refraction' => $request->target_refraction,
                'calculated_iol_power_right' => $calculationResults['right_eye'] ?? null,
                'calculated_iol_power_left' => $calculationResults['left_eye'] ?? null,
                'recommended_lens_model' => $request->recommended_lens_model,
                'recommended_lens_manufacturer' => $request->recommended_lens_manufacturer,
                'calculation_parameters' => $request->calculation_parameters,
                'results_summary' => $calculationResults['summary'] ?? null,
                'notes' => $request->notes,
                'calculated_by' => Auth::id(),
                'is_verified' => false,
            ]);

            $calculation->load(['patient', 'examination', 'calculatedBy']);

            return response()->json([
                'success' => true,
                'data' => $calculation,
                'message' => 'IOL hesaplaması başarıyla oluşturuldu'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IOL hesaplaması oluşturulurken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $calculation = PatientIolCalculation::with([
                'patient',
                'examination',
                'calculatedBy',
                'verifiedBy'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $calculation,
                'message' => 'IOL hesaplama detayları başarıyla getirildi'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IOL hesaplama detayları getirilirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $calculation = PatientIolCalculation::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'calculation_date' => 'sometimes|required|date',
                'algorithm_used' => 'sometimes|required|in:srk_t,hoffer_q,holladay_1,holladay_2,haigis,barrett,olsen,kane',
                'axial_length_right' => 'nullable|numeric|min:20|max:35',
                'axial_length_left' => 'nullable|numeric|min:20|max:35',
                'k1_right' => 'nullable|numeric|min:30|max:50',
                'k1_left' => 'nullable|numeric|min:30|max:50',
                'k2_right' => 'nullable|numeric|min:30|max:50',
                'k2_left' => 'nullable|numeric|min:30|max:50',
                'acd_right' => 'nullable|numeric|min:2|max:5',
                'acd_left' => 'nullable|numeric|min:2|max:5',
                'lens_constant' => 'nullable|numeric|min:100|max:140',
                'target_refraction' => 'nullable|numeric|min:-10|max:10',
                'recommended_lens_model' => 'nullable|string|max:255',
                'recommended_lens_manufacturer' => 'nullable|string|max:255',
                'calculation_parameters' => 'nullable|array',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasyon hatası',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Eğer biometri verileri değiştiyse yeniden hesapla
            $recalculate = false;
            $biometryFields = ['axial_length_right', 'axial_length_left', 'k1_right', 'k1_left', 
                              'k2_right', 'k2_left', 'acd_right', 'acd_left', 'lens_constant'];
            
            foreach ($biometryFields as $field) {
                if ($request->has($field) && $request->$field != $calculation->$field) {
                    $recalculate = true;
                    break;
                }
            }

            if ($recalculate) {
                $calculationResults = $this->calculateIOL($request->all());
                $request->merge([
                    'calculated_iol_power_right' => $calculationResults['right_eye'] ?? null,
                    'calculated_iol_power_left' => $calculationResults['left_eye'] ?? null,
                    'results_summary' => $calculationResults['summary'] ?? null,
                ]);
            }

            $calculation->update($request->only([
                'calculation_date', 'algorithm_used', 'axial_length_right', 'axial_length_left',
                'k1_right', 'k1_left', 'k2_right', 'k2_left', 'acd_right', 'acd_left',
                'lens_constant', 'target_refraction', 'calculated_iol_power_right',
                'calculated_iol_power_left', 'recommended_lens_model', 'recommended_lens_manufacturer',
                'calculation_parameters', 'results_summary', 'notes'
            ]));

            $calculation->load(['patient', 'examination', 'calculatedBy']);

            return response()->json([
                'success' => true,
                'data' => $calculation,
                'message' => 'IOL hesaplaması başarıyla güncellendi'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IOL hesaplaması güncellenirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $calculation = PatientIolCalculation::findOrFail($id);
            $calculation->delete();

            return response()->json([
                'success' => true,
                'message' => 'IOL hesaplaması başarıyla silindi'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IOL hesaplaması silinirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hesaplamayı doğrula
     */
    public function verify(string $id): JsonResponse
    {
        try {
            $calculation = PatientIolCalculation::findOrFail($id);
            $calculation->markAsVerified(Auth::id());

            return response()->json([
                'success' => true,
                'data' => $calculation,
                'message' => 'IOL hesaplaması başarıyla doğrulandı'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IOL hesaplaması doğrulanırken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Advanced AI-powered IOL calculation
     */
    public function calculateAdvanced(Request $request): JsonResponse
    {
        try {
            $patientId = $request->route('patient');
            
            $validator = Validator::make($request->all(), [
                'biometry_data' => 'required|array',
                'topography_data' => 'nullable|array',
                'device_type' => 'nullable|string',
                'use_ai_prediction' => 'boolean',
                'use_3d_modeling' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $advancedService = app(AdvancedIolCalculationService::class);
            
            $results = [];

            // AI-powered prediction if requested
            if ($request->use_ai_prediction) {
                $patient = Patient::findOrFail($patientId);
                $aiPrediction = $advancedService->predictIOLPowerWithAI(
                    $patient->toArray(),
                    $request->biometry_data
                );
                $results['ai_prediction'] = $aiPrediction;
            }

            // 3D eye modeling if requested
            if ($request->use_3d_modeling) {
                $eyeModel = $advancedService->create3DEyeModel(
                    $request->biometry_data,
                    $request->topography_data ?? []
                );
                $results['eye_model'] = $eyeModel;
            }

            // Advanced toric calculation if topography provided
            if ($request->topography_data) {
                $toricResults = $advancedService->calculateAdvancedToric(
                    $request->biometry_data,
                    $request->topography_data
                );
                $results['toric_calculation'] = $toricResults;
            }

            // Quality control analysis
            $qualityControl = $advancedService->performAdvancedQualityControl(
                array_merge($request->biometry_data, ['topography' => $request->topography_data])
            );
            $results['quality_control'] = $qualityControl;

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Advanced IOL calculation completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Advanced calculation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Acquire biometry data from connected devices
     */
    public function acquireBiometry(Request $request): JsonResponse
    {
        try {
            $patientId = $request->route('patient');
            
            $validator = Validator::make($request->all(), [
                'device_type' => 'required|in:iol_master_700,lenstar_ls900,pentacam_axi'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $advancedService = app(AdvancedIolCalculationService::class);
            
            $biometryData = $advancedService->fetchBiometryFromDevice(
                $request->device_type,
                $patientId
            );

            return response()->json([
                'success' => true,
                'data' => $biometryData,
                'message' => 'Biometry data acquired successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Device acquisition failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get surgeon personalized constants
     */
    public function getSurgeonConstants(Request $request): JsonResponse
    {
        try {
            $surgeonId = Auth::id();
            
            $validator = Validator::make($request->all(), [
                'iol_model' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $advancedService = app(AdvancedIolCalculationService::class);
            
            $constants = $advancedService->getSurgeonOptimizedConstants(
                $surgeonId,
                $request->iol_model
            );

            return response()->json([
                'success' => true,
                'data' => $constants,
                'message' => 'Surgeon constants retrieved'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get surgeon constants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hasta IOL geçmişi
     */
    public function patientHistory(string $patientId): JsonResponse
    {
        try {
            $calculations = PatientIolCalculation::with(['examination', 'calculatedBy', 'verifiedBy'])
                ->where('patient_id', $patientId)
                ->orderBy('calculation_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $calculations,
                'message' => 'Hasta IOL hesaplama geçmişi başarıyla getirildi'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hasta IOL hesaplama geçmişi getirilirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * IOL hesaplama algoritması
     */
    private function calculateIOL(array $data): array
    {
        $algorithm = $data['algorithm_used'];
        $results = [];

        // Sağ göz hesaplaması
        if (!empty($data['axial_length_right']) && !empty($data['k1_right']) && !empty($data['k2_right'])) {
            $results['right_eye'] = $this->calculateIOLPower(
                $data['axial_length_right'],
                $data['k1_right'],
                $data['k2_right'],
                $data['acd_right'] ?? 3.0,
                $data['lens_constant'] ?? 118.0,
                $algorithm
            );
        }

        // Sol göz hesaplaması
        if (!empty($data['axial_length_left']) && !empty($data['k1_left']) && !empty($data['k2_left'])) {
            $results['left_eye'] = $this->calculateIOLPower(
                $data['axial_length_left'],
                $data['k1_left'],
                $data['k2_left'],
                $data['acd_left'] ?? 3.0,
                $data['lens_constant'] ?? 118.0,
                $algorithm
            );
        }

        // Özet bilgiler
        $results['summary'] = [
            'algorithm' => $algorithm,
            'average_power' => null,
            'power_difference' => null,
        ];

        if (isset($results['right_eye']) && isset($results['left_eye'])) {
            $results['summary']['average_power'] = ($results['right_eye'] + $results['left_eye']) / 2;
            $results['summary']['power_difference'] = abs($results['right_eye'] - $results['left_eye']);
        }

        return $results;
    }

    /**
     * IOL gücü hesaplama - Gelişmiş algoritmalar
     */
    private function calculateIOLPower(float $axialLength, float $k1, float $k2, float $acd, float $lensConstant, string $algorithm): float
    {
        $avgK = ($k1 + $k2) / 2;
        $AL = $axialLength;
        $ACD = $acd;
        $A = $lensConstant;
        $TR = 0; // Target refraction - varsayılan 0
        
        switch ($algorithm) {
            case 'srk_t':
                // SRK/T Formula - Accurate implementation
                $offset = $AL > 24.2 ? -3.446 : -1.729;
                $X = ($AL - 3.336) / ($avgK - 6.8);
                $ACDest = $X <= -2 ? 
                    4.2 + 1.75 * $AL :
                    ($X <= -1.5 ? 3.2 + 0.62 * $AL : 3.37 + 0.68 * $AL);
                $L = $AL + $offset + 0.68 * $ACDest;
                $power = $A - 2.5 * $L - 0.9 * $avgK + $TR;
                break;
                
            case 'hoffer_q':
                // Hoffer Q Formula - Accurate implementation
                $ACD_HQ = $AL < 23 ? 
                    4.2 + 1.75 * $AL :
                    ($AL > 26 ? 2.9 + 0.62 * $AL : 3.37 + 0.68 * $AL);
                $M = 1.336 * ($avgK / 337.5);
                $G = $ACD_HQ * $M;
                $H = $AL + $ACD_HQ;
                $power = $A - 2.5 * $H - 0.9 * $avgK + $TR - $G;
                break;
                
            case 'holladay_1':
                // Holladay 1 Formula - Accurate implementation
                $SF = 1.336 / (($avgK / 337.5) * ($AL / 22.5));
                $ACD_H1 = $AL < 20 ? 
                    4.2 + 1.75 * $AL :
                    ($AL > 26 ? 2.9 + 0.54 * $AL : 3.37 + 0.68 * $AL);
                $ELP = $ACD_H1 + $SF;
                $power = (1336 / ($AL - $ELP)) - (1.336 / (1.336 / ($avgK / 337.5) - ($ELP / 1000))) + $TR;
                break;
                
            case 'holladay_2':
                // Holladay 2 Formula (simplified version)
                $WTW = 12.0; // Assuming average white-to-white distance
                $ACD_H2 = 0.56 + $AL * 0.098 + $avgK * 0.02 + $WTW * 0.15 + $ACD * 0.6;
                $power = (1336 / ($AL - $ACD_H2)) - (1.336 / (1.336 / ($avgK / 337.5) - ($ACD_H2 / 1000))) + $TR;
                break;
                
            default:
                // Default to SRK/T
                $offset = $AL > 24.2 ? -3.446 : -1.729;
                $ACDest = $AL > 24.5 ? 
                    3.37 + 0.68 * $AL :
                    3.2 + 0.62 * $AL;
                $L = $AL + $offset + 0.68 * $ACDest;
                $power = $A - 2.5 * $L - 0.9 * $avgK + $TR;
        }
        
        return round($power * 4) / 4; // Round to nearest 0.25D
    }
}
