# external-calendar-providers Specification

## Purpose
TBD - created by archiving change external-calendar-ical-sync. Update Purpose after archive.
## Requirements
### Requirement: Four provider configurations are managed in Settings
The system SHALL provide fixed configuration records for Airbnb, Booking.com, HomeToGo, and Google Calendar. The admin Settings page SHALL allow authorized users to edit each provider URL and enabled state.

#### Scenario: Owner updates provider settings
- **WHEN** a `host_owner` or `super_admin` submits a valid provider URL and enabled state
- **THEN** the values are persisted for that provider
- **AND** the provider becomes eligible or ineligible for synchronization and availability according to the enabled state

#### Scenario: Unauthorized user cannot manage providers
- **WHEN** a `host_keeper` or unauthenticated user attempts to view or submit provider configuration
- **THEN** access is denied
- **AND** no provider configuration is changed

### Requirement: Provider synchronization status is visible
The Settings page SHALL show each provider's enabled state, current synchronization status, last attempt, last successful synchronization, imported-event count, and latest error when present.

#### Scenario: Never-synchronized provider is shown as inactive
- **WHEN** an enabled provider has never completed a successful synchronization
- **THEN** the page marks it as never synchronized
- **AND** it contributes no external blocks

#### Scenario: Provider status reflects outcomes
- **WHEN** a provider synchronization starts, succeeds, or fails
- **THEN** the page can display syncing, successful, or error status respectively
- **AND** the latest timestamps, count, and error message reflect the corresponding attempt

### Requirement: Disabled providers are retained but ignored
Disabling a provider SHALL preserve its stored configuration and current events while excluding all of its events from availability and calendar presentation.

#### Scenario: Re-enabling restores the provider's current events
- **WHEN** a provider with retained events is disabled and later re-enabled before a new sync
- **THEN** its retained events become eligible again
- **AND** the system does not require recreating the provider records

