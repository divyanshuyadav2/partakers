<?php

namespace App\Livewire\Traits;

trait HasWorkTypes
{
    /**
     * Return the work types array (label => tooltip).
     */
    public function getWorkTypes(): array
    {
        return [
            "Full-time" => "Standard 35–40 hours per week, usually with benefits.",
            "Part-time" => "Fewer hours than full-time, often flexible but fewer benefits.",
            "Temporary" => "Short-term roles, often through staffing agencies.",
            "Contract" => "Fixed-term agreements for specific projects or durations.",
            "Freelance" => "Independent work, self-employed, paid per project/task.",
            "Internship" => "Training-focused, often for students or career starters.",
            "Apprenticeship" => "Skill-building with on-the-job training plus study.",
            "Seasonal" => "Work tied to specific times of year (e.g., holiday retail, agriculture).",
            "Casual/On-call" => "Work only when needed, no guaranteed hours.",
            "Remote" => "Work done outside the office, often online.",
            "Gig work" => "Short, flexible jobs (like ride-sharing or delivery apps).",
            "Self-employment" => "Running your own business or services.",
            "Voluntary work" => "Unpaid, for social or community benefit.",
            "Job sharing" => "Two people split one full-time role.",
            "Zero-hour contracts" => "No guaranteed hours; work offered as needed.",
        ];
    }
}
