"""Real Chromium acceptance checks against an isolated Docker Compose preview.

Requires: pip install playwright==1.56.0 && python -m playwright install chromium
Runs only against an explicitly configured disposable test URL.
"""

import os
import re
import unittest

from playwright.sync_api import sync_playwright, expect


BASE_URL = os.environ.get("E2E_BASE_URL")


@unittest.skipUnless(BASE_URL, "E2E_BASE_URL must point to an isolated test stack")
class PublicBrowserJourneysTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.playwright = sync_playwright().start()
        cls.browser = cls.playwright.chromium.launch(headless=True)

    @classmethod
    def tearDownClass(cls):
        cls.browser.close()
        cls.playwright.stop()

    def setUp(self):
        self.context = self.browser.new_context(
            base_url=BASE_URL,
            viewport={"width": 1280, "height": 900},
        )
        self.page = self.context.new_page()

    def tearDown(self):
        self.context.close()

    def test_search_filters_offer_details_and_draft_visibility(self):
        page = self.page
        page.goto("/offers")
        expect(page.get_by_role("heading", name="Student offers")).to_be_visible()
        expect(page.get_by_text("4 offers found")).to_be_visible()
        expect(page.get_by_text("Draft Offer — DEMO")).to_have_count(0)

        page.get_by_role("searchbox", name="Search offers").fill("Student Design")
        page.get_by_label("Brand", exact=True).select_option("demo-cloud-studio")
        page.get_by_label("Category", exact=True).select_option("demo-software")
        page.get_by_role("button", name="Apply filters").click()

        expect(page).to_have_url(re.compile(r"brand=demo-cloud-studio"))
        expect(page.get_by_text("1 offer found")).to_be_visible()
        page.get_by_role("link", name="Student Design Kit — DEMO").first.click()
        expect(page.get_by_role("heading", name="Student Design Kit — DEMO")).to_be_visible()
        expect(page.get_by_text("Who is eligible?")).to_be_visible()
        expect(page.get_by_text("How to redeem")).to_be_visible()
        expect(page.get_by_role("link", name="Visit official partner")).to_have_count(0)

        page.goto("/offers/demo-unpublished-offer")
        expect(page.get_by_role("heading", name="We couldn't find that page")).to_be_visible()

    def test_mobile_discovery_filters_and_collection_navigation(self):
        page = self.page
        page.set_viewport_size({"width": 360, "height": 800})
        page.goto("/offers")
        brand = page.get_by_label("Brand", exact=True)
        category = page.get_by_label("Category", exact=True)
        self.assertAlmostEqual(brand.bounding_box()["y"], category.bounding_box()["y"], delta=2)
        brand.select_option("demo-cloud-studio")
        category.select_option("demo-software")
        page.get_by_role("button", name="Apply filters").click()
        expect(page.get_by_text("2 offers found")).to_be_visible()
        expect(page.get_by_text("Brand: Demo Cloud Studio")).to_be_visible()
        page.get_by_role("link", name="Reset all").click()
        expect(page.get_by_text("4 offers found")).to_be_visible()

        page.goto("/brands/demo-cloud-studio")
        expect(page.get_by_role("navigation", name="Breadcrumb")).to_be_visible()
        expect(page.get_by_role("heading", name="Demo Cloud Studio")).to_be_visible()
        page.get_by_role("navigation", name="Breadcrumb").get_by_role("link", name="Brands").click()
        expect(page.get_by_role("heading", name="Discover brands")).to_be_visible()

        page.goto("/categories/demo-software")
        expect(page.get_by_role("navigation", name="Breadcrumb")).to_be_visible()
        page.get_by_role("navigation", name="Breadcrumb").get_by_role("link", name="All offers").click()
        expect(page.get_by_role("heading", name="Student offers")).to_be_visible()

        page.goto("/search?q=nonexistent-unique-test")
        expect(page.get_by_role("heading", name="No offers found")).to_be_visible()
        page.get_by_role("link", name="See all offers").click()
        expect(page.get_by_role("heading", name="Student offers")).to_be_visible()

    def test_home_to_brands_guides_and_admin_login(self):
        page = self.page
        page.goto("/")
        expect(page.get_by_role("heading", level=1)).to_be_visible()
        page.get_by_role("navigation", name="Main navigation").get_by_role("link", name="Brands").click()
        expect(page.get_by_role("heading", name="Discover brands")).to_be_visible()
        page.get_by_role("link", name="Explore offers").first.click()
        expect(page.get_by_role("heading", level=1)).to_be_visible()

        page.goto("/articles")
        page.get_by_role("link", name="How to compare student offers — DEMO").click()
        expect(page.get_by_role("heading", name="How to compare student offers — DEMO")).to_be_visible()

        page.goto("/admin/login")
        expect(page.get_by_label("Email address")).to_be_visible()
        expect(page.locator('input[type="password"]')).to_be_visible()

    def test_mobile_navigation_keyboard_and_no_page_horizontal_overflow(self):
        page = self.page
        page.set_viewport_size({"width": 375, "height": 812})
        for path in ["/", "/offers", "/brands", "/articles", "/offers/demo-student-design-kit"]:
            page.goto(path)
            expect(page.locator("main")).to_be_visible()
            self.assertLessEqual(
                page.evaluate("document.documentElement.scrollWidth - window.innerWidth"),
                2,
                "Unexpected page-wide horizontal overflow on " + path,
            )

        page.goto("/")
        page.keyboard.press("Tab")
        expect(page.get_by_role("link", name="Skip to content")).to_be_focused()
        page.keyboard.press("Enter")
        self.assertEqual(page.evaluate("location.hash"), "#main")
        page.get_by_role("navigation", name="Main navigation").get_by_role("link", name="Search").click()
        expect(page.get_by_role("heading", name="Search student offers")).to_be_visible()


if __name__ == "__main__":
    unittest.main()
