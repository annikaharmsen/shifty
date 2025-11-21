# Shifty

An AI-powered employee scheduling system designed for restaurants and hospitality establishments. Shifty automates the complex task of creating weekly shift schedules by considering employee availability, preferred hours, role requirements, and historical scheduling patterns.

## Overview

Managing employee schedules in restaurants is notoriously complex, balancing availability, fairness, labor costs, and business needs. Shifty streamlines this process by using intelligent algorithms to generate optimal schedule drafts that respect both employee preferences and operational requirements.

## Features

-   **Smart Schedule Generation** - AI-powered algorithm creates schedule drafts based on templates, employee availability, weekly hour requirements, and previous scheduling patterns
-   **Flexible Availability Management** - Employees can set available/unavailable/preferred time blocks with recurring patterns (daily, weekly, monthly)
-   **Schedule Templates** - Create and reuse shift templates for different weeks or seasons
-   **Multi-establishment Support** - Manage multiple locations under a single company umbrella
-   **Role-based Scheduling** - Assign shifts based on employee roles (server, cook, bartender, host, etc.)
-   **On-call Shift Support** - Handle backup shifts with deprioritized assignment logic
-   **Historical Schedule Storage** - Track and learn from previous scheduling decisions

## Tech Stack

**Backend:**

-   PHP 8.2
-   Laravel 12
-   Carbon (date/time handling)

**Development Tools:**

-   Composer
-   NPM/Concurrently
-   Laravel Sail (Docker)
-   Laravel Pail (log viewer)
-   PHPUnit

## Architecture

### Core Domain Models

**Company** - Top-level organization containing establishments and employees

**Establishment** - Individual restaurant or location with its own schedules and templates

**Employee** - Staff member with availability rules, role assignments, and scheduling preferences

**Schedule** - Weekly schedule containing assigned shifts for a specific establishment

**ScheduleTemplate** - Reusable template defining shift patterns for a typical week

**Shift** - Individual work shift with start time, duration, role requirement, and optional assignee

**AvailabilityRule** - Time-based rule defining when an employee is available, unavailable, or prefers to work (supports one-time or recurring patterns)

**Role** - Job function (e.g., server, cook, bartender) with associated permissions and wage information

### Value Objects

The system uses immutable value objects for domain logic:

-   **Timeblock** - Represents a time period with start and duration, provides overlap detection and hour calculations
-   **RecurringTimeblock** - Extends Timeblock with frequency support (daily, weekly, monthly)
-   **Availability** - Aggregates multiple AvailabilityRule objects to determine employee availability for any given timeblock

### Scheduling Algorithm

The scheduling service follows a constraint-based approach:

1. **Initialize** - Load template shifts for the target week
2. **Order** - Prioritize shifts by importance (non-on-call first)
3. **Assign** - For each unassigned shift, find the top candidate based on:
    - Employee has required role
    - Employee is fully available during shift time
    - Employee hasn't exceeded weekly hour maximum
    - Employee hasn't been scheduled during overlapping time
    - Priority given to employees closer to minimum weekly hours
4. **Validate** - Ensure all constraints are satisfied

Future enhancements will incorporate:

-   Employee feedback from previous schedules
-   Manager configuration preferences
-   Shift length constraints
-   AI/ML-based pattern recognition

## Database Schema

Key relationships:

-   Company → 1+ Establishments
-   Company → 1+ Employees
-   Establishment → 0+ Schedules
-   Establishment → 0+ Schedule Templates
-   Schedule → 0+ Shifts
-   Schedule Template → 1+ Template Shifts
-   Employee → 0+ Availability Rules
-   Employee → 1+ Role Assignments
-   Shift → 0/1 Employee (assignee)

## Roadmap

**Current Status:** Core domain models and availability logic implemented. Scheduling algorithm in development.

**Upcoming Features:**

-   Complete scheduling algorithm implementation
-   User authentication and authorization
-   RESTful API endpoints for CRUD operations
-   Frontend UI for schedule management
-   Employee feedback system
-   Manager override capabilities
-   Reporting and analytics

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure your code:

-   Follows PSR-12 coding standards
-   Includes appropriate tests
-   Updates documentation as needed

## License

GNU Affero General Public License v3.0 - see [LICENSE](LICENSE) for details.

## Acknowledgments

-   Built with [Laravel](https://laravel.com)
-   Date/time handling powered by [Carbon](https://carbon.nesbot.com)
-   Frontend styling with [Tailwind CSS](https://tailwindcss.com)
