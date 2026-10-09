<?php

namespace App\Enums;

enum RoleName: string
{
    case Student = 'student';
    case Faculty = 'faculty';
    case Hod = 'hod';
    case AdministrativeStaff = 'administrative_staff';
    case HrOfficer = 'hr_officer';
    case FinanceOfficer = 'finance_officer';
    case Examiner = 'examiner';
    case InstitutionalAdmin = 'institutional_admin';
    case Applicant = 'applicant';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Faculty => 'Faculty',
            self::Hod => 'Head of Department',
            self::AdministrativeStaff => 'Administrative Staff',
            self::HrOfficer => 'HR Officer',
            self::FinanceOfficer => 'Finance Officer',
            self::Examiner => 'Examiner',
            self::InstitutionalAdmin => 'Institutional Admin',
            self::Applicant => 'Applicant',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
