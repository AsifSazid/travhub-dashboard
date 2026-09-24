<?php
// PATH: /api/loans/loan_calc.php
//
// EMI calculation helpers, supporting both interest methods the user
// confirmed: reducing_balance (interest recalculated on the remaining
// principal each month) and flat (interest always calculated on the
// original principal for the full tenure).

/**
 * Calculates the EMI amount and a full amortization schedule.
 *
 * @param float  $principal
 * @param float  $annualRatePct  Annual interest rate as a percentage (e.g. 12 for 12%). 0 = interest-free.
 * @param int    $tenureMonths
 * @param string $method 'reducing_balance' | 'flat'
 * @return array ['emi_amount' => float, 'schedule' => [['installment_no','principal_component','interest_component','total_due'], ...]]
 */
function calculateEmi(float $principal, float $annualRatePct, int $tenureMonths, string $method): array
{
    if ($annualRatePct <= 0) {
        // Interest-free: EMI is simply principal divided evenly.
        $emi = round($principal / $tenureMonths, 2);
        $schedule = [];
        $remaining = $principal;
        for ($i = 1; $i <= $tenureMonths; $i++) {
            $thisPrincipal = ($i === $tenureMonths) ? $remaining : $emi; // last installment absorbs rounding remainder
            $schedule[] = [
                'installment_no' => $i,
                'principal_component' => round($thisPrincipal, 2),
                'interest_component' => 0.0,
                'total_due' => round($thisPrincipal, 2),
            ];
            $remaining = round($remaining - $thisPrincipal, 2);
        }
        return ['emi_amount' => $emi, 'schedule' => $schedule];
    }

    $monthlyRate = $annualRatePct / 12 / 100;

    if ($method === 'flat') {
        // Flat rate: interest = principal * rate * tenure(years), spread evenly.
        // total interest for the whole tenure, calculated once on the original principal
        $totalInterest = $principal * ($annualRatePct / 100) * ($tenureMonths / 12);
        $totalPayable  = $principal + $totalInterest;
        $emi = round($totalPayable / $tenureMonths, 2);

        $principalPerMonth = round($principal / $tenureMonths, 2);
        $interestPerMonth  = round($totalInterest / $tenureMonths, 2);

        $schedule = [];
        for ($i = 1; $i <= $tenureMonths; $i++) {
            $schedule[] = [
                'installment_no' => $i,
                'principal_component' => $principalPerMonth,
                'interest_component' => $interestPerMonth,
                'total_due' => round($principalPerMonth + $interestPerMonth, 2),
            ];
        }
        return ['emi_amount' => $emi, 'schedule' => $schedule];
    }

    // reducing_balance (the standard bank EMI formula):
    // EMI = P * r * (1+r)^n / ((1+r)^n - 1)
    $r = $monthlyRate;
    $n = $tenureMonths;
    $factor = pow(1 + $r, $n);
    $emi = round($principal * $r * $factor / ($factor - 1), 2);

    $schedule = [];
    $balance = $principal;
    for ($i = 1; $i <= $n; $i++) {
        $interestComponent = round($balance * $r, 2);
        $principalComponent = ($i === $n) ? round($balance, 2) : round($emi - $interestComponent, 2); // last installment clears exact remaining balance
        $schedule[] = [
            'installment_no' => $i,
            'principal_component' => $principalComponent,
            'interest_component' => $interestComponent,
            'total_due' => round($principalComponent + $interestComponent, 2),
        ];
        $balance = round($balance - $principalComponent, 2);
    }

    return ['emi_amount' => $emi, 'schedule' => $schedule];
}