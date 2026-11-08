<?php
require_once __DIR__ . '/student_data.php';

$pageTitle = 'Fiscal Year';
$activePage = 'fiscal-year';
$academicClasses = get_academic_classes_for_current_user();
$availableFiscalYears = [];
foreach ($academicClasses as $academicClass) {
    $year = trim((string) $academicClass['fiscal_year']);
    if ($year !== '' && !in_array($year, $availableFiscalYears, true)) {
        $availableFiscalYears[] = $year;
    }
}
$requestedFiscalYear = is_string($_GET['fiscal_year'] ?? null)
    ? trim($_GET['fiscal_year'])
    : '';
$selectedFiscalYear = in_array($requestedFiscalYear, $availableFiscalYears, true)
    ? $requestedFiscalYear
    : ($availableFiscalYears[0] ?? '');
$selectedClasses = array_values(array_filter(
    $academicClasses,
    static fn(array $class): bool => $class['fiscal_year'] === $selectedFiscalYear
));

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>
<main class="main-content">
    <div class="page-header">
        <h2>Fiscal Year</h2>
        <p>Choose an academic year to see the classes and semesters assigned to it.</p>
    </div>
    <section class="card" aria-labelledby="fiscal-year-title">
        <h3 id="fiscal-year-title">Academic year</h3>
        <?php if ($availableFiscalYears): ?>
            <form class="list-filter-form" method="get">
                <label for="fiscal-year-select">Fiscal year</label>
                <select id="fiscal-year-select" name="fiscal_year">
                    <?php foreach ($availableFiscalYears as $year): ?>
                        <option value="<?= e($year) ?>" <?= $year === $selectedFiscalYear ? 'selected' : '' ?>>
                            <?= e($year) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" type="submit">Show year</button>
            </form>
        <?php else: ?>
            <p class="empty-state">No fiscal years have been assigned to your classes yet.</p>
        <?php endif; ?>
    </section>
    <?php if ($selectedFiscalYear !== ''): ?>
        <section class="card" aria-labelledby="academic-classes-title">
            <h3 id="academic-classes-title">Classes in <?= e($selectedFiscalYear) ?></h3>
            <?php
            $rows = array_map(
                static fn(array $class): array => [
                    $class['name'],
                    $class['code'],
                    $class['semester'] ?: 'Not assigned',
                    $class['teacher'] ?: 'Not assigned',
                ],
                $selectedClasses
            );
            render_table(
                ['Course', 'Class code', 'Semester', 'Teacher'],
                $rows,
                'No classes are assigned to this fiscal year.'
            );
            ?>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
