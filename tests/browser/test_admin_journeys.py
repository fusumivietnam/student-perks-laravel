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

    def test_offer_draft_edit_publish_and_unpublish_through_real_browser(self):
        page = self.page
        self.login("browser-admin@ci.invalid")
        expect(page).to_have_url(re.compile(r"/admin/?$"))

        page.goto("/admin/offers")
        draft_row = page.get_by_role("row").filter(has_text="Browser CI Draft Offer")
        expect(draft_row).to_be_visible()
        draft_row.locator('a[href$="/edit"]').first.click()
        edit_url = page.url
        expect(page.locator('input[id$=".title"]')).to_have_value("Browser CI Draft Offer")
        toggle = page.get_by_role("switch", name=re.compile("Published", re.I))
        expect(toggle).not_to_be_checked()

        # Saving a draft must preserve the public 404.
        page.locator('input[id$=".title"]').fill("Browser CI Edited Offer")
        page.get_by_role("button", name="Save changes").click()
        expect(page.get_by_text("Saved", exact=False)).to_be_visible()
        page.goto("/offers/browser-e2e-draft-offer")
        expect(page.get_by_role("heading", name="We couldn't find that page")).to_be_visible()

        # Reload the edit route from the server, not browser-back cache.
        page.goto(edit_url)
        expect(page.locator('input[id$=".title"]')).to_have_value("Browser CI Edited Offer")
        toggle = page.get_by_role("switch", name=re.compile("Published", re.I))
        toggle.click()
        expect(toggle).to_be_checked()
        # Filament entangles this Alpine switch to Livewire state asynchronously.
        page.wait_for_timeout(600)
        page.get_by_role("button", name="Save changes").click()
        page.wait_for_timeout(900)
        page.reload()
        expect(page.get_by_role("switch", name=re.compile("Published", re.I))).to_be_checked()
        page.goto("/offers/browser-e2e-draft-offer")
        expect(page.get_by_role("heading", name="Browser CI Edited Offer")).to_be_visible()

        # Restore this isolated CI-only draft using the same real form.
        page.goto(edit_url)
        toggle = page.get_by_role("switch", name=re.compile("Published", re.I))
        expect(toggle).to_be_checked()
        toggle.click()
        expect(toggle).not_to_be_checked()
        page.wait_for_timeout(600)
        page.get_by_role("button", name="Save changes").click()
        page.wait_for_timeout(900)
        page.reload()
        expect(page.get_by_role("switch", name=re.compile("Published", re.I))).not_to_be_checked()
        page.goto("/offers/browser-e2e-draft-offer")
        expect(page.get_by_role("heading", name="We couldn't find that page")).to_be_visible()


    def test_create_offer_validates_input_and_saves_private_draft(self):
        page = self.page
        self.login("browser-admin@ci.invalid")
        expect(page).to_have_url(re.compile(r"/admin/?$"))
        page.goto("/admin/offers/create")

        # Empty submission must not create a record or navigate away.
        page.get_by_role("button", name="Create", exact=True).click()
        expect(page).to_have_url(re.compile(r"/admin/offers/create$"))
        expect(page.locator('input[id$=".title"]')).to_be_visible()

        # Relationship selects can render as native selects or Filament
        # accessible comboboxes depending on the chosen widget.
        def choose_relationship(field, label):
            control = page.locator('[id="form.' + field + '"]')
            expect(control).to_be_visible()
            if control.evaluate("(element) => element.tagName === 'SELECT'"):
                control.select_option(label=label)
            else:
                control.click()
                page.get_by_role("option", name=label, exact=True).click()

        choose_relationship("brand_id", "Demo Learning Lab")
        choose_relationship("category_id", "Learning (Demo)")
        page.locator('input[id$=".title"]').fill("Browser Created Draft Offer")
        page.locator('input[id$=".slug"]').fill("browser-created-draft-offer")
        page.locator('textarea[id$=".summary"]').fill("Private draft created through Chromium.")
        page.locator('textarea[id$=".description"]').fill("Not a real partner offer.")
        page.locator('input[id$=".partner_url"]').fill("https://example.org/")
        page.get_by_role("button", name="Create", exact=True).click()

        expect(page).to_have_url(re.compile(r"/admin/offers/\d+/edit$"))
        expect(page.locator('input[id$=".title"]')).to_have_value("Browser Created Draft Offer")
        expect(page.get_by_role("switch", name=re.compile("Published", re.I))).not_to_be_checked()
        page.goto("/offers/browser-created-draft-offer")
        expect(page.get_by_role("heading", name="We couldn't find that page")).to_be_visible()

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
