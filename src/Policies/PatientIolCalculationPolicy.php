<?php

namespace Docratech\IolCalculator\Policies;

use Docratech\IolCalculator\Models\User;
use Docratech\IolCalculator\Models\PatientIolCalculation;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientIolCalculationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any IOL calculations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('iol-calculations.view') || 
               $user->hasRole(['admin', 'doctor']);
    }

    /**
     * Determine whether the user can view the IOL calculation.
     */
    public function view(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can view all IOL calculations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can view IOL calculations in their clinic
        if ($user->hasRole('doctor')) {
            return $iolCalculation->clinic_id === $user->clinic_id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.view') && 
               $iolCalculation->clinic_id === $user->clinic_id;
    }

    /**
     * Determine whether the user can create IOL calculations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('iol-calculations.create') || 
               $user->hasRole(['admin', 'doctor']);
    }

    /**
     * Determine whether the user can update the IOL calculation.
     */
    public function update(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can update all IOL calculations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can update IOL calculations they created or in their clinic
        if ($user->hasRole('doctor')) {
            return $iolCalculation->doctor_id === $user->id || 
                   $iolCalculation->clinic_id === $user->clinic_id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.update') && 
               $iolCalculation->clinic_id === $user->clinic_id;
    }

    /**
     * Determine whether the user can delete the IOL calculation.
     */
    public function delete(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can delete all IOL calculations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can delete IOL calculations they created
        if ($user->hasRole('doctor')) {
            return $iolCalculation->doctor_id === $user->id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.delete');
    }

    /**
     * Determine whether the user can verify the IOL calculation.
     */
    public function verify(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can verify all IOL calculations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can verify IOL calculations in their clinic
        if ($user->hasRole('doctor')) {
            return $iolCalculation->clinic_id === $user->clinic_id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.verify') && 
               $iolCalculation->clinic_id === $user->clinic_id;
    }

    /**
     * Determine whether the user can view IOL calculation history.
     */
    public function viewHistory(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can view all IOL calculation history
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can view IOL calculation history in their clinic
        if ($user->hasRole('doctor')) {
            return $iolCalculation->clinic_id === $user->clinic_id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.history') && 
               $iolCalculation->clinic_id === $user->clinic_id;
    }

    /**
     * Determine whether the user can generate IOL calculation reports.
     */
    public function generateReport(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can generate reports for all IOL calculations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can generate reports for IOL calculations in their clinic
        if ($user->hasRole('doctor')) {
            return $iolCalculation->clinic_id === $user->clinic_id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.report') && 
               $iolCalculation->clinic_id === $user->clinic_id;
    }

    /**
     * Determine whether the user can view lens recommendations.
     */
    public function viewLensRecommendations(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can view all lens recommendations
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can view lens recommendations in their clinic
        if ($user->hasRole('doctor')) {
            return $iolCalculation->clinic_id === $user->clinic_id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.lens-recommendations') && 
               $iolCalculation->clinic_id === $user->clinic_id;
    }

    /**
     * Determine whether the user can export IOL calculation data.
     */
    public function export(User $user, PatientIolCalculation $iolCalculation): bool
    {
        // Admin can export all IOL calculation data
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctor can export IOL calculation data in their clinic
        if ($user->hasRole('doctor')) {
            return $iolCalculation->clinic_id === $user->clinic_id;
        }

        // Check specific permission
        return $user->hasPermissionTo('iol-calculations.export') && 
               $iolCalculation->clinic_id === $user->clinic_id;
    }
}
