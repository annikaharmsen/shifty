# Shifty

An intelligent employee scheduling system for restaurants and hospitality establishments. Shifty automates weekly shift scheduling using constraint-based algorithms that balance employee availability, preferred hours, role requirements, and fair workload distribution.

## Features

- **Automated Schedule Generation** - Constraint-based algorithm assigns shifts while respecting availability, roles, and weekly hour targets
- **Flexible Availability Management** - Recurring availability rules with support for daily, weekly, and monthly patterns
- **Role-Based Scheduling** - Multi-role support ensures the right qualified employee is assigned to each shift
- **Schedule Templates** - Define reusable shift patterns for typical weeks or seasonal variations
- **On-Call Shift Support** - Differentiated handling for backup shifts with deprioritized assignment
- **Weekly Hours Balancing** - Tracks and optimizes employee utilization to match target weekly hours
- **Conflict Detection** - Identifies impossible-to-schedule shifts before assignment
- **Comprehensive Testing** - 100+ automated tests ensure reliable scheduling logic

## Tech Stack

**Backend:**
- PHP 8.2
- Laravel 12.0
- Carbon for date/time handling
- SQLite database (development)

**Development Tools:**
- PHPUnit 11.5 for testing
- Laravel Sail (Docker environment)
- Laravel Pail (log monitoring)
- Composer scripts for automation

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and NPM

### Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd shifty
```

2. Run the automated setup script:
```bash
composer setup
```

This will:
- Install PHP dependencies
- Copy `.env.example` to `.env`
- Generate application key
- Run database migrations
- Install NPM dependencies
- Build frontend assets

3. (Optional) Configure your database in `.env` if not using SQLite

## Usage

### Start Development Server

Run all services concurrently (web server, queue worker, log viewer, and Vite):

```bash
composer dev
```

This starts:
- Laravel server on `http://localhost:8000`
- Queue worker for background jobs
- Real-time log viewer (Pail)
- Vite dev server for hot module replacement

## Architecture

### Core Domain Models

- **Company** - Organization containing establishments and employees
- **Establishment** - Individual location with schedules and templates
- **Employee** - Staff with availability rules, roles, and weekly hour targets
- **Schedule** - Weekly shift schedule for an establishment
- **ScheduleTemplate** - Reusable pattern defining standard shifts
- **Shift** - Individual work assignment with time, role, and optional assignee
- **AvailabilityRule** - Time-based rule for employee availability (one-time or recurring)
- **Role** - Job function (server, cook, bartender, etc.)

### Value Objects

Immutable domain objects for time-based logic:

- **Timeblock** - Time period with start and duration, handles overlap detection
- **RecurringTimeblock** - Extends Timeblock with frequency patterns
- **Availability** - Aggregates AvailabilityRule objects to determine availability

### Scheduling Algorithm

The AutoScheduleService uses a constraint-based backtracking approach:

1. **Prioritization** - Shifts are ordered by:
   - Non-on-call shifts first
   - Longer duration shifts prioritized (harder to schedule)
   - Fewer available candidates prioritized

2. **Candidate Selection** - For each shift, find employees who:
   - Have the required role
   - Are fully available during shift time
   - Haven't exceeded weekly hour targets
   - Don't have overlapping shift assignments

3. **Employee Ranking** - Candidates are sorted by:
   - Remaining schedulable hours (prioritizes under-utilized employees)
   - Ensures fair distribution of shifts

4. **Assignment** - Assigns best candidate, validates constraints

5. **Backtracking** - If no valid assignment exists, backtracks to previous decisions

6. **Utilization Tracking** - Monitors weekly hours:
   - Properly utilized: 90-110% of target hours
   - Under-utilized: < 90% of target hours
   - Over-utilized: > 110% of target hours (algorithm prevents > 150%)

### Database Schema

Key relationships:

- Company → many Establishments
- Company → many Employees
- Establishment → many Schedules
- Establishment → many Schedule Templates
- Schedule → many Shifts
- Schedule Template → many Template Shifts
- Employee → many Availability Rules
- Employee → many Roles (many-to-many)
- Shift → one Employee (assignee, optional)

## Testing

The project includes comprehensive test coverage:

- **Unit Tests** - Individual service and model logic
  - `AutoScheduleServiceTest` - Core scheduling algorithm (13 tests)
  - `CandidateFinderTest` - Employee candidacy logic
  - `EmployeeSorterTest` - Employee ranking
  - `ShiftPrioritizer Test` - Shift ordering

- **Integration Tests** - Realistic scenarios with generated data
  - Weekly schedule assignment with multiple employees and roles
  - Utilization metrics tracking
  - Conflict detection
  - Partial schedule handling

Test builders and generators create realistic test data:
- `EmployeeBuilder` - Configurable employee creation
- `ShiftBuilder` - Flexible shift construction
- `EmployeeGenerator` - Realistic employee pools
- `ScheduleTemplateGenerator` - Full week schedule templates

## Roadmap

**Current Status:** Core scheduling engine complete with backtracking algorithm and utilization tracking. In active development.

**Next Milestones:**

- [ ] More flexible employee schedulability restraints (max-hours, target hour error margin, time between shifts, etc.)
- [ ] REST API endpoints for schedule CRUD operations
- [ ] User authentication and authorization
- [ ] Frontend UI for schedule management
- [ ] Employee feedback system for continuous improvement
- [ ] Manager override capabilities
- [ ] Analytics and reporting dashboard
- [ ] Multi-establishment scheduling support
- [ ] Shift swap and trade functionality

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Write tests for your changes
4. Ensure all tests pass
5. Follow PSR-12 coding standards
6. Commit your changes with clear messages
7. Push to your branch and open a Pull Request

Please ensure your code:
- Includes unit tests for new functionality
- Maintains existing test coverage
- Follows Laravel best practices
- Updates documentation as needed

## License

GNU Affero General Public License v3.0 - see [LICENSE](LICENSE) for details.

## Acknowledgments

- Built with [Laravel](https://laravel.com)
- Date/time handling by [Carbon](https://carbon.nesbot.com)
- Inspired by real-world restaurant scheduling challenges