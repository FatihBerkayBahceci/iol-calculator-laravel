<?php

namespace Docratech\IolCalculator\Services;

use Docratech\IolCalculator\Models\Patient;
use Docratech\IolCalculator\Models\PatientIolCalculation;
use Docratech\IolCalculator\Models\IolOutcomeData;
use Docratech\IolCalculator\Models\BiometryDevice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AdvancedIolCalculationService
{
    // Real-time device integration endpoints
    private array $deviceAPIs = [
        'iol_master_700' => [
            'endpoint' => 'http://192.168.1.100:8080/api/v1/measurement',
            'auth_key' => 'zeiss_api_key',
            'timeout' => 30
        ],
        'lenstar_ls900' => [
            'endpoint' => 'http://192.168.1.101:8080/api/v1/biometry',
            'auth_key' => 'haag_streit_key',
            'timeout' => 30
        ],
        'pentacam_axi' => [
            'endpoint' => 'http://192.168.1.102:8080/api/v1/topography',
            'auth_key' => 'oculus_api_key',
            'timeout' => 45
        ]
    ];

    // AI/ML Model Configuration
    private array $mlModels = [
        'outcome_predictor' => [
            'model_path' => '/app/ml_models/iol_outcome_predictor_v2.pkl',
            'api_endpoint' => 'http://ml-server:5000/predict',
            'confidence_threshold' => 0.85,
            'version' => '2.3.1'
        ],
        'refractive_surprises' => [
            'model_path' => '/app/ml_models/refractive_surprise_detector_v1.pkl',
            'api_endpoint' => 'http://ml-server:5000/detect_surprise',
            'confidence_threshold' => 0.75
        ],
        'formula_optimizer' => [
            'model_path' => '/app/ml_models/formula_optimizer_v3.pkl',
            'api_endpoint' => 'http://ml-server:5000/optimize_formula',
            'retrain_interval' => 30 // days
        ]
    ];

    // Advanced IOL Database with detailed specifications
    private array $advancedLensDatabase = [
        'premium_monofocal' => [
            'alcon_clareon' => [
                'name' => 'Clareon AutonoMe',
                'manufacturer' => 'Alcon',
                'material' => 'Clareon Hydrophobic Acrylic',
                'a_constant' => 119.1,
                'surgeon_factor' => 0.0,
                'haigis_a0' => 0.229,
                'haigis_a1' => 0.011,
                'haigis_a2' => 0.205,
                'holladay_sf' => 1.75,
                'optic_diameter' => 6.0,
                'overall_diameter' => 13.0,
                'edge_design' => 'frosted_square',
                'chromophore' => true,
                'blue_light_filter' => true,
                'spherical_aberration' => -0.20,
                'power_range' => [-5.0, 34.0],
                'power_steps' => 0.25,
                'refractive_index' => 1.55,
                'water_content' => 1.5,
                'uvb_transmission' => 1,
                'violet_transmission' => 15,
                'blue_transmission' => 80
            ],
            'tecnis_eyhance' => [
                'name' => 'Tecnis Eyhance',
                'manufacturer' => 'Johnson & Johnson Vision',
                'material' => 'UV-absorbing hydrophobic acrylic',
                'a_constant' => 119.3,
                'surgeon_factor' => 0.0,
                'haigis_a0' => 0.245,
                'haigis_a1' => 0.014,
                'haigis_a2' => 0.190,
                'holladay_sf' => 1.75,
                'optic_diameter' => 6.0,
                'overall_diameter' => 13.0,
                'edge_design' => 'continuous_360_square',
                'chromophore' => false,
                'blue_light_filter' => false,
                'spherical_aberration' => -0.27,
                'power_range' => [5.0, 34.0],
                'power_steps' => 0.25,
                'enhanced_intermediate' => true,
                'depth_of_focus' => 1.75
            ]
        ],
        'premium_multifocal' => [
            'panoptix_trifocal' => [
                'name' => 'AcrySof IQ PanOptix Trifocal',
                'manufacturer' => 'Alcon',
                'material' => 'UV/Blue-filtering Hydrophobic Acrylic',
                'a_constant' => 118.7,
                'focal_points' => [0, 2.17, 3.25], // Distance, Intermediate, Near
                'light_distribution' => [
                    'distance' => 88,
                    'intermediate' => 12,
                    'near' => 12
                ],
                'diffractive_steps' => 15,
                'central_zone' => 4.5,
                'chromatic_aberration_correction' => true,
                'halo_rating' => 'minimal',
                'glare_rating' => 'minimal'
            ],
            'tecnis_synergy' => [
                'name' => 'Tecnis Synergy',
                'manufacturer' => 'Johnson & Johnson Vision',
                'material' => 'UV-absorbing hydrophobic acrylic',
                'a_constant' => 119.0,
                'technology' => 'continuous_range_of_vision',
                'echelette_design' => true,
                'achromatic_technology' => true,
                'range_of_vision' => 'continuous 33cm to infinity',
                'pupil_independence' => true
            ]
        ],
        'premium_toric' => [
            'alcon_toric_calculator' => [
                'surgical_induced_astigmatism' => [
                    'temporal_incision' => 0.1,
                    'superior_incision' => 0.5,
                    'coupling_ratio' => 1.0
                ],
                'posterior_corneal_astigmatism' => [
                    'with_the_rule' => -0.3,
                    'against_the_rule' => -0.1,
                    'oblique' => -0.2
                ],
                'axis_calculation' => [
                    'vector_analysis' => true,
                    'sia_adjustment' => true,
                    'pca_adjustment' => true
                ]
            ]
        ]
    ];

    // Surgeon-specific optimization database
    private array $surgeonOptimization = [];

    /**
     * Real-time biometry device integration
     */
    public function fetchBiometryFromDevice(string $deviceType, string $patientId): array
    {
        if (!isset($this->deviceAPIs[$deviceType])) {
            throw new \Exception("Unsupported device type: {$deviceType}");
        }

        $config = $this->deviceAPIs[$deviceType];
        
        try {
            $response = Http::timeout($config['timeout'])
                ->withHeaders([
                    'Authorization' => "Bearer {$config['auth_key']}",
                    'Content-Type' => 'application/json'
                ])
                ->post($config['endpoint'], [
                    'patient_id' => $patientId,
                    'measurement_type' => 'comprehensive_biometry',
                    'quality_threshold' => 0.95
                ]);

            if (!$response->successful()) {
                throw new \Exception("Device communication failed: {$response->body()}");
            }

            $data = $response->json();
            
            return $this->processBiometryData($data, $deviceType);

        } catch (\Exception $e) {
            \Log::error("Biometry device integration failed", [
                'device' => $deviceType,
                'patient_id' => $patientId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Advanced keratometry analysis with topography integration
     */
    public function analyzeAdvancedKeratometry(array $topographyData): array
    {
        $analysis = [
            'keratometry' => [
                'k1' => $topographyData['k1'],
                'k2' => $topographyData['k2'],
                'k_max' => $topographyData['k_max'] ?? null,
                'k_apex' => $topographyData['k_apex'] ?? null
            ],
            'astigmatism_analysis' => [
                'magnitude' => abs($topographyData['k1'] - $topographyData['k2']),
                'axis' => $topographyData['axis'],
                'regularity_index' => $this->calculateRegularityIndex($topographyData),
                'asymmetry_index' => $this->calculateAsymmetryIndex($topographyData),
                'surface_asymmetry_index' => $this->calculateSurfaceAsymmetryIndex($topographyData)
            ],
            'corneal_aberrations' => [
                'spherical_aberration' => $this->calculateSphericalAberration($topographyData),
                'coma' => $this->calculateComa($topographyData),
                'trefoil' => $this->calculateTrefoil($topographyData),
                'higher_order_aberrations' => $this->calculateHigherOrderAberrations($topographyData)
            ],
            'quality_indices' => [
                'central_k_reading_index' => $this->calculateCentralKIndex($topographyData),
                'surface_regularity_index' => $this->calculateSurfaceRegularityIndex($topographyData),
                'predicted_visual_acuity' => $this->predictVisualAcuity($topographyData)
            ],
            'clinical_indices' => [
                'keratoconus_index' => $this->calculateKeratoconusIndex($topographyData),
                'pellucid_index' => $this->calculatePellucidIndex($topographyData),
                'post_lasik_ectasia_risk' => $this->calculateEctasiaRisk($topographyData)
            ]
        ];

        return $analysis;
    }

    /**
     * AI-powered IOL power prediction with machine learning
     */
    public function predictIOLPowerWithAI(array $patientData, array $biometryData): array
    {
        // Prepare feature vector for ML model
        $features = $this->prepareMlFeatures($patientData, $biometryData);
        
        try {
            // Call ML prediction service
            $response = Http::timeout(60)
                ->post($this->mlModels['outcome_predictor']['api_endpoint'], [
                    'features' => $features,
                    'model_version' => $this->mlModels['outcome_predictor']['version'],
                    'return_confidence' => true,
                    'return_explanation' => true
                ]);

            if (!$response->successful()) {
                throw new \Exception("ML prediction service failed");
            }

            $prediction = $response->json();
            
            return [
                'predicted_iol_power' => $prediction['iol_power'],
                'confidence_score' => $prediction['confidence'],
                'prediction_interval' => $prediction['interval'], // 95% confidence interval
                'feature_importance' => $prediction['feature_importance'],
                'similar_cases' => $prediction['similar_cases'] ?? [],
                'risk_factors' => $this->identifyRiskFactors($features, $prediction),
                'recommendation' => $this->generateAiRecommendation($prediction)
            ];

        } catch (\Exception $e) {
            \Log::error("AI prediction failed", ['error' => $e->getMessage()]);
            
            // Fallback to traditional calculation
            return $this->fallbackPrediction($biometryData);
        }
    }

    /**
     * Advanced toric calculator with real-time axis optimization
     */
    public function calculateAdvancedToric(array $biometryData, array $topographyData): array
    {
        // Extract corneal astigmatism from topography
        $cornealAstigmatism = $this->extractCornealAstigmatism($topographyData);
        
        // Calculate posterior corneal astigmatism
        $posteriorCA = $this->calculatePosteriorCornealAstigmatism($cornealAstigmatism);
        
        // Account for surgical induced astigmatism
        $surgicalSIA = $this->getSurgicalInducedAstigmatism(Auth::user());
        
        // Vector analysis
        $vectorAnalysis = $this->performVectorAnalysis([
            'corneal_astigmatism' => $cornealAstigmatism,
            'posterior_ca' => $posteriorCA,
            'surgical_sia' => $surgicalSIA
        ]);
        
        // Recommend toric IOL
        $toricRecommendation = $this->recommendToricIOL($vectorAnalysis);
        
        // Calculate alignment tolerance
        $alignmentTolerance = $this->calculateAlignmentTolerance($toricRecommendation);
        
        return [
            'corneal_analysis' => $cornealAstigmatism,
            'posterior_corneal_adjustment' => $posteriorCA,
            'surgical_induced_astigmatism' => $surgicalSIA,
            'vector_analysis' => $vectorAnalysis,
            'recommended_iol' => $toricRecommendation,
            'alignment_specifications' => [
                'target_axis' => $toricRecommendation['axis'],
                'tolerance_range' => $alignmentTolerance,
                'rotation_stability' => $this->predictRotationStability($biometryData),
                'capsular_bag_diameter' => $this->estimateCapsularBagDiameter($biometryData)
            ],
            'surgical_planning' => [
                'incision_location' => $this->recommendIncisionLocation($vectorAnalysis),
                'marking_strategy' => $this->recommendMarkingStrategy(),
                'intraoperative_guidance' => $this->generateIntraoperativeGuidance($toricRecommendation)
            ]
        ];
    }

    /**
     * 3D Eye Modeling and Visualization
     */
    public function create3DEyeModel(array $biometryData, array $topographyData): array
    {
        // Generate 3D coordinates for eye structures
        $eyeModel = [
            'cornea' => [
                'anterior_surface' => $this->generateCorneaSurface($topographyData, 'anterior'),
                'posterior_surface' => $this->generateCorneaSurface($topographyData, 'posterior'),
                'thickness_map' => $this->generateThicknessMap($topographyData),
                'elevation_map' => $this->generateElevationMap($topographyData)
            ],
            'anterior_chamber' => [
                'depth_map' => $this->generateACDepthMap($biometryData),
                'angle_analysis' => $this->analyzeAnteriorChamberAngle($biometryData),
                'volume' => $this->calculateACVolume($biometryData)
            ],
            'crystalline_lens' => [
                'position' => $this->calculateLensPosition($biometryData),
                'thickness' => $biometryData['lens_thickness'] ?? 4.0,
                'diameter' => $this->estimateLensDiameter($biometryData),
                'curvature' => $this->estimateLensCurvature($biometryData)
            ],
            'retina' => [
                'axial_length_map' => $this->generateAxialLengthMap($biometryData),
                'macular_profile' => $this->generateMacularProfile($biometryData)
            ],
            'iol_simulation' => [
                'position_simulation' => $this->simulateIOLPosition($biometryData),
                'optical_simulation' => $this->simulateOpticalPerformance($biometryData),
                'visual_simulation' => $this->simulateVisualOutcome($biometryData)
            ]
        ];

        // Generate visualization data
        $visualization = [
            'mesh_data' => $this->generateMeshData($eyeModel),
            'ray_tracing_data' => $this->generateRayTracingData($eyeModel),
            'cross_sections' => $this->generateCrossSections($eyeModel),
            'annotations' => $this->generateAnnotations($eyeModel)
        ];

        return [
            'eye_model' => $eyeModel,
            'visualization' => $visualization,
            'measurements' => $this->extractModelMeasurements($eyeModel),
            'quality_metrics' => $this->assessModelQuality($eyeModel)
        ];
    }

    /**
     * Post-operative outcome tracking and learning
     */
    public function trackPostoperativeOutcome(int $calculationId, array $outcomeData): array
    {
        DB::beginTransaction();
        
        try {
            // Store outcome data
            $outcome = IolOutcomeData::create([
                'calculation_id' => $calculationId,
                'postop_refraction' => $outcomeData['refraction'],
                'postop_visual_acuity' => $outcomeData['visual_acuity'],
                'patient_satisfaction' => $outcomeData['satisfaction'] ?? null,
                'complications' => $outcomeData['complications'] ?? [],
                'follow_up_date' => $outcomeData['follow_up_date'],
                'surgeon_id' => Auth::id()
            ]);

            // Calculate prediction accuracy
            $calculation = PatientIolCalculation::find($calculationId);
            $accuracy = $this->calculatePredictionAccuracy($calculation, $outcome);

            // Update surgeon-specific optimization
            $this->updateSurgeonOptimization($calculation, $outcome, $accuracy);

            // Retrain ML models if needed
            if ($this->shouldRetrainModel()) {
                $this->scheduleModelRetraining();
            }

            DB::commit();

            return [
                'outcome_id' => $outcome->id,
                'prediction_accuracy' => $accuracy,
                'absolute_error' => abs($outcome->postop_refraction - $calculation->predicted_refraction),
                'surgeon_performance' => $this->getSurgeonPerformanceMetrics(Auth::id()),
                'learning_insights' => $this->generateLearningInsights($calculation, $outcome)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Surgeon personalization and formula optimization
     */
    public function getSurgeonOptimizedConstants(int $surgeonId, string $iolModel): array
    {
        $cacheKey = "surgeon_constants_{$surgeonId}_{$iolModel}";
        
        return Cache::remember($cacheKey, 3600, function() use ($surgeonId, $iolModel) {
            // Get surgeon's historical outcomes
            $outcomes = IolOutcomeData::whereHas('calculation', function($q) use ($surgeonId) {
                $q->where('calculated_by', $surgeonId);
            })->with('calculation')->get();

            if ($outcomes->count() < 30) {
                // Not enough data, return standard constants
                return $this->getStandardConstants($iolModel);
            }

            // Calculate personalized constants
            $personalized = $this->calculatePersonalizedConstants($outcomes, $iolModel);
            
            return [
                'personalized_a_constant' => $personalized['a_constant'],
                'surgeon_factor' => $personalized['surgeon_factor'],
                'confidence_level' => $personalized['confidence'],
                'number_of_cases' => $outcomes->count(),
                'mean_absolute_error' => $personalized['mae'],
                'standard_deviation' => $personalized['std_dev'],
                'last_updated' => now(),
                'recommendation' => $this->generatePersonalizationRecommendation($personalized)
            ];
        });
    }

    /**
     * Advanced quality control and validation
     */
    public function performAdvancedQualityControl(array $calculationData): array
    {
        $qualityChecks = [
            'biometry_quality' => $this->assessBiometryQuality($calculationData),
            'measurement_consistency' => $this->checkMeasurementConsistency($calculationData),
            'outlier_detection' => $this->detectOutliers($calculationData),
            'formula_appropriateness' => $this->assessFormulaAppropriateness($calculationData),
            'risk_stratification' => $this->performRiskStratification($calculationData),
            'confidence_assessment' => $this->assessCalculationConfidence($calculationData)
        ];

        $overallQuality = $this->calculateOverallQualityScore($qualityChecks);
        
        $recommendations = $this->generateQualityRecommendations($qualityChecks);

        return [
            'quality_score' => $overallQuality,
            'quality_checks' => $qualityChecks,
            'recommendations' => $recommendations,
            'approval_status' => $overallQuality >= 85 ? 'approved' : 'requires_review',
            'reviewer_notes' => $this->generateReviewerNotes($qualityChecks)
        ];
    }

    // Helper methods for advanced calculations...
    private function processBiometryData(array $data, string $deviceType): array
    {
        // Device-specific data processing
        switch ($deviceType) {
            case 'iol_master_700':
                return $this->processIOLMaster700Data($data);
            case 'lenstar_ls900':
                return $this->processLenstarData($data);
            case 'pentacam_axi':
                return $this->processPentacamData($data);
            default:
                return $data;
        }
    }

    private function processIOLMaster700Data(array $data): array
    {
        return [
            'axial_length' => $data['biometry']['axial_length'],
            'k1' => $data['keratometry']['k1'],
            'k2' => $data['keratometry']['k2'],
            'axis' => $data['keratometry']['axis'],
            'acd' => $data['anterior_chamber']['depth'],
            'lens_thickness' => $data['lens']['thickness'],
            'white_to_white' => $data['cornea']['diameter'],
            'pupil_diameter' => $data['pupil']['diameter'],
            'quality_factors' => [
                'al_snr' => $data['quality']['al_signal_noise_ratio'],
                'k_quality' => $data['quality']['keratometry_quality'],
                'measurement_confidence' => $data['quality']['overall_confidence']
            ],
            'device_info' => [
                'model' => 'IOLMaster 700',
                'software_version' => $data['device']['software_version'],
                'calibration_date' => $data['device']['last_calibration']
            ]
        ];
    }

    // Additional helper methods would be implemented here...
    // This is a comprehensive framework for advanced IOL calculation
}