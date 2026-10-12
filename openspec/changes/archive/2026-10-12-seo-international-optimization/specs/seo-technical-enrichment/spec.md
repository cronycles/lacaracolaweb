## ADDED Requirements

### Requirement: Sitemap entries carry lastmod and priority
`/sitemap.xml` SHALL include a `<lastmod>` and `<priority>` value for each URL, to help search engines prioritize crawling of the most important pages (homepage, apartment) over secondary pages (terms, rules).

#### Scenario: Homepage has the highest priority
- **WHEN** `/sitemap.xml` is generated
- **THEN** each locale's homepage URL SHALL have the highest `<priority>` value among that locale's URLs

### Requirement: Review schema only reflects real, publicly visible reviews
The `VacationRental` JSON-LD SHALL include an `AggregateRating`/`Review` block only if the same rating/review data is genuinely displayed to visitors on the public reviews page.

#### Scenario: No review schema without visible reviews
- **WHEN** there is no real review/rating data rendered on the public reviews page
- **THEN** the `VacationRental` JSON-LD SHALL NOT include an `AggregateRating` or `Review` block
