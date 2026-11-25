"""Real cross-browser public acceptance checks against disposable Docker Compose.

Requires: pip install playwright==1.56.0 && python -m playwright install chromium firefox webkit
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
        engine = os.environ.get("E2E_BROWSER", "chromium")
        if engine not in ("chromium", "firefox", "webkit"):
            raise RuntimeError("Unsupported E2E_BROWSER: " + engine)
        cls.browser = getattr(cls.playwright, engine).launch(headless=True)

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
        page.get_by_role("link", name="How to compare student offers — DEMO", exact=True).click()
        expect(page.get_by_role("heading", name="How to compare student offers — DEMO")).to_be_visible()

        page.goto("/admin/login")
        expect(page.get_by_label("Email address")).to_be_visible()
        expect(page.locator('input[type="password"]')).to_be_visible()

    def test_public_heading_consistency_across_viewports(self):
        page = self.page
        for width in (360, 390, 768, 1024, 1440):
            page.set_viewport_size({"width": width, "height": 900})
            for path in ("/offers", "/search", "/brands", "/brands/demo-cloud-studio",
                         "/categories/demo-software", "/articles",
                         "/articles/demo-how-to-compare-offers", "/offers/demo-student-design-kit",
                         "/pages/browser-ci-live-page"):
                with self.subTest(width=width, path=path):
                    response = page.goto(path)
                    self.assertEqual(response.status, 200)
                    expect(page.locator('main [data-ui="public-page-heading"] h1')).to_be_visible()
                    expect(page.locator("main h1")).to_have_count(1)
                    self.assertLessEqual(
                        page.evaluate("document.documentElement.scrollWidth - window.innerWidth"),
                        2, f"Horizontal overflow at {width}px on {path}",
                    )

    def test_public_routes_have_no_runtime_errors_or_broken_local_assets(self):
        """Catch JS errors and failed first-party CSS/JS/images not visible in DOM assertions."""
        base = BASE_URL.rstrip("/") + "/"
        for path in ("/", "/offers", "/search", "/brands", "/articles",
                     "/offers/demo-student-design-kit", "/pages/browser-ci-live-page"):
            with self.subTest(path=path):
                page = self.context.new_page()
                errors = []
                bad_assets = []
                try:
                    page.on("pageerror", lambda error: errors.append(str(error)))

                    def check_response(response):
                        request = response.request
                        if (response.url.startswith(base)
                                and request.resource_type in ("stylesheet", "script", "image")
                                and response.status >= 400):
                            bad_assets.append(f"{response.status} {response.url}")

                    page.on("response", check_response)
                    response = page.goto(path, wait_until="load")
                    self.assertIsNotNone(response)
                    self.assertEqual(response.status, 200)
                    self.assertEqual(errors, [], f"Uncaught JavaScript errors on {path}")
                    self.assertEqual(bad_assets, [], f"Broken first-party assets on {path}")
                finally:
                    page.close()

    def test_public_landmarks_form_labels_and_link_names_across_pages(self):
        """Flag missing structural semantics and unnamed interactive controls.

        This is a deterministic smoke gate, not an automated WCAG audit.
        """
        page = self.page
        for path in ("/", "/offers", "/search", "/brands", "/articles",
                     "/offers/demo-student-design-kit", "/pages/browser-ci-live-page"):
            with self.subTest(path=path):
                page.goto(path)
                issues = page.evaluate("""() => {
                    const issues = [];
                    const visible = el => !!(el.getClientRects().length) &&
                        getComputedStyle(el).visibility !== 'hidden';
                    const name = el => (
                        el.getAttribute('aria-label') ||
                        (el.getAttribute('aria-labelledby') || '').split(/\\s+/)
                          .map(id => document.getElementById(id)?.textContent || '').join(' ') ||
                        el.innerText ||
                        el.textContent ||
                        el.querySelector('img[alt]')?.getAttribute('alt') ||
                        ''
                    ).trim();
                    if (!document.documentElement.lang.trim()) issues.push('missing html lang');
                    if (document.querySelectorAll('main').length !== 1) issues.push('main landmark count');
                    if (document.querySelectorAll('h1').length !== 1) issues.push('h1 count');
                    for (const [index, el] of [...document.querySelectorAll('a, button')].entries()) {
                        if (visible(el) && !name(el)) issues.push('unnamed link/button #' + index);
                    }
                    for (const el of document.querySelectorAll('input:not([type=hidden]), select, textarea')) {
                        if (!visible(el)) continue;
                        const id = el.id;
                        const labelled = !!(el.getAttribute('aria-label') ||
                            el.getAttribute('aria-labelledby') ||
                            (id && [...document.querySelectorAll('label[for]')]
                              .some(label => label.htmlFor === id)) ||
                            el.closest('label'));
                        if (!labelled) issues.push('unlabelled form control ' + (id || el.name || el.tagName));
                    }
                    return issues;
                }""")
                self.assertEqual(issues, [], f"Accessibility smoke issues on {path}: {issues}")

    def test_keyboard_focus_visible_for_public_search_and_navigation(self):
        page = self.page
        page.goto("/")
        page.keyboard.press("Tab")
        expect(page.get_by_role("link", name="Skip to content")).to_be_focused()
        page.keyboard.press("Enter")
        self.assertEqual(page.evaluate("document.activeElement.id"), "main")

        page.goto("/offers")
        search = page.get_by_role("searchbox", name="Search offers")
        search.focus()
        self.assertTrue(search.evaluate("""el => {
            const style = getComputedStyle(el);
            return style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
        }"""), "Search keyboard focus must have a visible outline")
        search.fill("Student")
        page.keyboard.press("Enter")
        expect(page.get_by_role("heading", name="Student offers")).to_be_visible()

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
