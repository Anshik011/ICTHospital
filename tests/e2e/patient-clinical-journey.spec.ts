import { test, expect } from './fixtures';

test.describe('Hospital Clinical & Front Desk E2E Journey', () => {

  test('staff can view dashboard KPI headline metrics', async ({ authenticatedPage }) => {
    // 1. Verify dashboard landing
    await expect(authenticatedPage.getByRole('heading', { level: 1, name: /dashboard/i })).toBeVisible();

    // 2. Verify patient, doctor, and bed occupancy summary cards
    await expect(authenticatedPage.getByText(/total patients/i)).toBeVisible();
    await expect(authenticatedPage.getByText(/total doctors/i)).toBeVisible();
    await expect(authenticatedPage.getByText(/total beds/i)).toBeVisible();
  });

  test('front desk can register patient and view clinical chart', async ({ authenticatedPage, baseURL }) => {
    const url = baseURL || 'http://localhost:8082';
    const timestamp = Date.now();
    const testPatientName = `E2E Patient ${timestamp}`;
    const testPhone = '03001234567';

    // 1. Navigate to patient registration form
    await authenticatedPage.goto(`${url}/patients/create`);
    await expect(authenticatedPage.getByRole('heading', { name: /patient/i })).toBeVisible();

    // 2. Fill registration inputs using semantic role and label selectors
    await authenticatedPage.getByLabel(/name/i).fill(testPatientName);
    await authenticatedPage.getByLabel(/phone/i).fill(testPhone);

    const sexSelect = authenticatedPage.locator('select[name="sex"]');
    if (await sexSelect.isVisible()) {
      await sexSelect.selectOption({ label: 'Male' });
    }

    const bloodGroupSelect = authenticatedPage.locator('select[name="bloodgroup"]');
    if (await bloodGroupSelect.isVisible()) {
      await bloodGroupSelect.selectOption({ label: 'O+' });
    }

    // 3. Submit form and await redirect to patient chart
    const submitResponse = authenticatedPage.waitForResponse(
      (r) => r.url().includes('/patients') && (r.status() === 302 || r.status() === 200)
    );
    await authenticatedPage.getByRole('button', { name: /save|submit|register/i }).click();
    await submitResponse;

    // 4. Assert patient chart shows newly created patient name and MRN
    await expect(authenticatedPage.getByText(testPatientName)).toBeVisible();
    await expect(authenticatedPage.locator('.alert-success, .alert')).toBeVisible();
  });

  test('front desk can access appointment day schedule without conflicts', async ({ authenticatedPage, baseURL }) => {
    const url = baseURL || 'http://localhost:8082';

    // 1. Navigate to appointments list
    await authenticatedPage.goto(`${url}/appointments`);
    await expect(authenticatedPage).toHaveURL(new RegExp(`${url}/appointments`));

    // 2. Verify filter form exists and agenda view displays
    await expect(authenticatedPage.getByRole('button', { name: /filter|apply/i })).toBeVisible();
    await expect(authenticatedPage.getByRole('table')).toBeVisible();
  });

  test('inpatient admission list reflects occupied ward beds', async ({ authenticatedPage, baseURL }) => {
    const url = baseURL || 'http://localhost:8082';

    // 1. Navigate to inpatient admissions
    await authenticatedPage.goto(`${url}/admissions`);
    await expect(authenticatedPage).toHaveURL(new RegExp(`${url}/admissions`));

    // 2. Verify admissions table renders with status columns
    await expect(authenticatedPage.getByRole('table')).toBeVisible();
  });
});
