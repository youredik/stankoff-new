import { expect, Page, test as playwrightTest } from "@playwright/test";

import { UserPage } from "./pages/UserPage";
import { SupportTicketPage } from "./pages/SupportTicketPage";

expect.extend({
  toBeOnLoginPage(page: Page) {
    if (page.url().match(/\/oidc\/realms\/stankoff\/protocol\/openid-connect\/auth/)) {
      return {
        message: () => "passed",
        pass: true,
      };
    }

    return {
      message: () => `toBeOnLoginPage() assertion failed.\nExpected "/oidc/realms/stankoff/protocol/openid-connect/auth", got "${page.url()}".`,
      pass: false,
    };
  },
});

type Test = {
  userPage: UserPage,
  supportTicketPage: SupportTicketPage,
}

export const test = playwrightTest.extend<Test>({
  userPage: async ({ page }, use) => {
    await use(new UserPage(page));
  },
  supportTicketPage: async ({ page }, use) => {
    await use(new SupportTicketPage(page));
  },
});

export { expect };
