import { AbstractPage } from "./AbstractPage";

export class SupportTicketPage extends AbstractPage {
  async navigateToSupportTickets() {
    await this.page.getByRole('link', { name: 'Заявки в ТП' }).click();
  }

  async selectFirstTicket() {
    await this.page.locator('tbody tr').first().click();
  }

  async getMediaUploadSection() {
    return this.page.getByText('Медиа файлы').locator('..').locator('..');
  }

  async getUploadArea() {
    const mediaSection = await this.getMediaUploadSection();
    return mediaSection.locator('[data-testid="upload-area"], .MuiPaper-root').filter({ hasText: 'Перетащите файлы сюда или нажмите для выбора' });
  }

  async uploadFile(filePath: string) {
    const uploadArea = await this.getUploadArea();
    const fileInput = uploadArea.locator('input[type="file"]');

    await fileInput.setInputFiles(filePath);
  }

  async dragAndDropFile(filePath: string) {
    // For drag and drop testing, we'll use the file input method
    // as drag and drop simulation is complex and unreliable
    // In a real scenario, you might want to implement proper drag and drop testing
    await this.uploadFile(filePath);
  }

  async waitForUploadComplete() {
    await this.page.waitForSelector('.MuiLinearProgress-root', { state: 'detached' });
  }

  async getUploadedFiles() {
    const mediaSection = await this.getMediaUploadSection();
    return mediaSection.locator('.MuiPaper-root').filter({ hasText: /Загруженные файлы/ }).locator('..').locator('div').filter({ has: this.page.locator('img, .MuiIcon-root') });
  }

  async getFileCount() {
    const uploadedFiles = await this.getUploadedFiles();
    return await uploadedFiles.count();
  }

  async deleteFile(index: number = 0) {
    const uploadedFiles = await this.getUploadedFiles();
    const fileItem = uploadedFiles.nth(index);
    const deleteButton = fileItem.locator('button').filter({ has: this.page.locator('.MuiSvgIcon-root').filter({ hasText: 'delete' }) });
    await deleteButton.click();
  }

  async downloadFile(index: number = 0) {
    const uploadedFiles = await this.getUploadedFiles();
    const fileItem = uploadedFiles.nth(index);
    const downloadButton = fileItem.locator('button').filter({ has: this.page.locator('.MuiSvgIcon-root').filter({ hasText: 'download' }) });

    const downloadPromise = this.page.waitForEvent('download');
    await downloadButton.click();
    return await downloadPromise;
  }

  async previewFile(index: number = 0) {
    const uploadedFiles = await this.getUploadedFiles();
    const fileItem = uploadedFiles.nth(index);
    const thumbnail = fileItem.locator('img').or(fileItem.locator('.MuiIcon-root'));
    await thumbnail.click();
  }

  async closePreview() {
    await this.page.getByRole('button', { name: 'close' }).click();
  }

  async getPreviewDialog() {
    return this.page.locator('.MuiDialog-root');
  }

  async getErrorMessage() {
    return this.page.locator('.MuiAlert-root').filter({ hasText: 'error' });
  }

  async waitForErrorMessage() {
    await this.page.waitForSelector('.MuiAlert-root', { state: 'visible' });
  }
}
