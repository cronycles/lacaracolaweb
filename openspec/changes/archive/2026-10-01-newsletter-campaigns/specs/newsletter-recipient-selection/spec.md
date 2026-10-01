## ADDED Requirements

### Requirement: Subscriber selection shows all newsletter subscribers
The admin recipient selector SHALL show every newsletter-subscribed person matching the current filters, including people without an email address, and SHALL visibly mark people without a usable email as unavailable and non-selectable.

#### Scenario: Subscriber has no email
- **WHEN** a newsletter subscriber has no usable email address
- **THEN** the person remains visible with an unavailable indicator and a disabled selection control

#### Scenario: Subscriber has an email
- **WHEN** a newsletter subscriber has a valid email address
- **THEN** the person is shown as eligible for selection

### Requirement: Administrator can select eligible subscribers in bulk
The recipient selector SHALL allow an authorized administrator to select one eligible subscriber, multiple eligible subscribers, or all eligible subscribers matching the active filter, while never selecting unavailable rows.

#### Scenario: Select individual recipients
- **WHEN** the administrator checks one or more eligible subscriber rows
- **THEN** only those eligible email addresses are included in the pending recipient set

#### Scenario: Select all filtered recipients
- **WHEN** the administrator chooses all eligible subscribers for the current filter
- **THEN** the server resolves all matching eligible subscribers, including those on other pagination pages, and excludes people without email

### Requirement: Administrator can add manual email recipients
The recipient selector SHALL accept one or more manually entered email addresses that do not need to belong to a stored `Person` record.

#### Scenario: Add valid manual addresses
- **WHEN** the administrator enters valid manual addresses
- **THEN** the addresses appear as recipients and are normalized and deduplicated before campaign creation

#### Scenario: Reject invalid manual addresses
- **WHEN** the administrator enters an invalid manual address
- **THEN** the system identifies the invalid entry and prevents campaign creation until it is removed or corrected

### Requirement: Duplicate addresses are sent once
The system SHALL deduplicate recipient addresses case-insensitively across selected subscribers and manually entered addresses before creating delivery records.

#### Scenario: Same address selected twice
- **WHEN** the same normalized address is selected through a subscriber and manually entered
- **THEN** only one delivery is created for that address