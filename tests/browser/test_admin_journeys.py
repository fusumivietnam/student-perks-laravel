"""Authenticated browser acceptance against disposable, guarded CI accounts only."""
import os
import re
import unittest

from playwright.sync_api import sync_playwright, expect

BASE_URL = os.environ.get("E2E_BASE_URL")
PASSWORD = "ci-browser-password-only"


@unittest.skipUnless(BASE_URL, "E2E_BASE_URL must point to isolated CI")
class AdminBrowserAcceptanceTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.playwright = sync_playwright().start()
        cls.browser = cls.playwright.chromium.launch(headless=True)

    @classmethod
    def tearDownClass(cls):
        cls.browser.close()
        cls.playwright.stop()

    def setUp(self):
        self.context = self.browser.new_context(base_url=BASE_URL, viewport={"width": 1280, "height": 900})
        self.page = self.context.new_page()

    def tearDown(self):
        self.context.close()

    def login(self, email):
        self.page.goto("/admin/login")
        self.page.get_by_label("Email address").fill(email)
        self.page.locator('input[type="password"]').fill(PASSWORD)
        self.page.get_by_role("button", name="Sign in").click()

    def test_admin_can_login_browse_cms_and_edit_offer_form(self):
        page = self.page
        self.login("browser-admin@ci.invalid")
        expect(page).to_have_url(re.compile(r"/admin/?$"))
        page.goto("/admin/offers")
        expect(page.get_by_text("Student Design Kit — DEMO")).to_be_visible()
        expect(page.get_by_role("columnheader", name="Partner CTA")).to_be_visible()
        page.goto("/admin/brands")
        expect(page.get_by_text("Demo Cloud Studio")).to_be_visible()
        page.goto("/admin/categories")
        expect(page.get_by_text("Software (Demo)")).to_be_visible()
        page.goto("/admin/articles")
        expect(page.get_by_text("How to compare student offers — DEMO")).to_be_visible()
        page.goto("/admin/pages")
        expect(page.get_by_role("heading", name=re.compile("Pages"))).to_be_visible()
        page.goto("/admin/offers/create")
        expect(page.locator('input[id$=".title"]')).to_be_visible()
        expect(page.locator('input[id$=".slug"]')).to_be_visible()
        expect(page.locator('input[id$=".partner_url"]')).to_be_visible()

    def test_regular_user_login_does_not_grant_admin_access(self):
        page = self.page
        self.login("browser-user@ci.invalid")
        response = page.goto("/admin/offers")
        self.assertTrue(page.url.endswith("/admin/login") or response.status == 403)
        expect(page.get_by_text("Student Design Kit — DEMO")).to_have_count(0)

    def test_guest_cannot_open_admin_create_page(self):
        page = self.page
        page.goto("/admin/offers/create")
        expect(page).to_have_url(re.compile(r"/admin/login"))
        expect(page.locator('input[type="password"]')).to_be_visible()


if __name__ == "__main__":
    unittest.main()
