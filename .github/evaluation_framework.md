# GitHub Copilot Instructions Evaluation Framework

This document provides a comprehensive evaluation framework to compare different approaches for providing GitHub Copilot coding agents with repository guidance.

## Evaluation Approaches

### 1. No Instructions (Baseline)
- No `.github/copilot-instructions.md` file
- Agent relies entirely on repository exploration and existing documentation
- Complete discovery-based workflow

### 2. Barebones Reference-Only Instructions
- Minimal instruction file (~1.7KB) that only:
  - Provides repository structure overview
  - Points to existing documentation files (CONTRIBUTING.md, /docs, phpcs.xml.dist)
  - Lists key configuration files without duplicating content
- Maintains single source of truth in existing docs

### 3. Comprehensive Instructions (Current)
- Detailed instruction file (~8KB) with:
  - Validated commands with timing expectations
  - Explicit safety warnings ("NEVER CANCEL" for long operations)
  - Complete workflow descriptions
  - Offline development alternatives

## Test Scenarios

### Scenario 1: Developer Onboarding
**Task**: Fresh developer needs to set up the development environment and run first tests.

**Key Actions**:
- Install dependencies (npm ci, composer install)
- Start WordPress environment (wp-env start)
- Run linting (PHPCS, PHPStan)
- Execute PHPUnit tests
- Build plugin distribution

**Success Criteria**:
- Environment setup completion within 30 minutes
- No cancelled long-running operations
- All commands executed correctly on first attempt

### Scenario 2: Bug Fix Implementation
**Task**: Fix a reported PHP warning in the abilities registration code.

**Key Actions**:
- Locate and understand the bug
- Write/modify PHPUnit tests to reproduce the issue
- Implement fix with minimal code changes
- Validate fix passes all tests
- Ensure code meets quality standards (PHPCS, PHPStan)

**Success Criteria**:
- Bug correctly identified and isolated
- Fix implemented without breaking existing functionality
- All quality checks pass

### Scenario 3: Feature Implementation
**Task**: Add a new REST API endpoint for ability validation.

**Key Actions**:
- Understand existing REST API architecture
- Implement new endpoint following WordPress standards
- Add comprehensive tests for new functionality
- Update documentation if required
- Validate integration with existing abilities

**Success Criteria**:
- Feature implemented following project conventions
- Complete test coverage for new functionality
- No regression in existing features

### Scenario 4: Troubleshooting Failed CI
**Task**: CI pipeline fails due to coding standards violations.

**Key Actions**:
- Identify specific linting failures
- Apply appropriate fixes (auto-fix where possible)
- Validate fixes locally before pushing
- Understand why failures occurred

**Success Criteria**:
- All CI checks pass after remediation
- Understanding of root cause prevents future occurrences

## Evaluation Metrics

### Quantitative Metrics

#### Task Completion Time
- **Target**: <30 minutes for onboarding, <60 minutes for implementation tasks
- **Measurement**: Time from task start to successful completion
- **Factors**: Discovery time, execution time, troubleshooting time

#### Error Rate
- **Command Execution Errors**: Failed commands due to incorrect usage
- **Cancelled Operations**: Long-running operations terminated prematurely
- **Quality Failures**: Code that fails linting/testing on first submission

#### Command Efficiency
- **Commands per Task**: Total commands executed to complete task
- **Repeated Commands**: Commands run multiple times due to errors
- **Discovery Commands**: Commands used to understand repository structure

### Qualitative Metrics

#### Solution Quality
- **Code Safety**: Avoidance of potentially harmful operations
- **Best Practices**: Adherence to WordPress and project conventions
- **Minimal Changes**: Surgical fixes that don't modify unnecessary code

#### Workflow Efficiency
- **Context Switching**: Frequency of switching between different information sources
- **Decision Confidence**: Agent confidence in chosen approaches
- **Recovery Speed**: Time to recover from errors or incorrect assumptions

## Expected Results by Approach

### No Instructions (Baseline)
**Strengths**:
- Complete flexibility and discovery capability
- No maintenance overhead
- Forces engagement with primary documentation

**Weaknesses**:
- High discovery time (>30 minutes for initial setup)
- Risk of cancelled long operations (wp-env: 10min, PHPUnit: 15min)
- Potential for unsafe operations or non-standard approaches
- High error rate during initial exploration

**Predicted Metrics**:
- Task completion: 45-90 minutes
- Error rate: 40-60%
- Command efficiency: High retry rate
- Quality: Variable, depends on discovery success

### Barebones Reference-Only
**Strengths**:
- Single source of truth maintenance
- Lower cognitive load
- Encourages documentation engagement
- Reduced duplication

**Weaknesses**:
- May miss critical timing warnings (wp-env startup delays)
- Requires multiple context switches between files
- Less explicit about safety considerations
- Discovery time for specific workflows

**Predicted Metrics**:
- Task completion: 25-45 minutes
- Error rate: 20-40% (reduced by documentation guidance)
- Command efficiency: Medium retry rate
- Quality: Good, guided by existing standards

### Comprehensive Instructions (Current)
**Strengths**:
- Explicit safety warnings prevent costly mistakes
- Validated timing expectations
- Complete workflow coverage
- Offline development support

**Weaknesses**:
- Higher maintenance overhead (8KB vs existing docs)
- Potential information duplication
- May reduce exploration of primary documentation
- Could become outdated if not maintained

**Predicted Metrics**:
- Task completion: 15-30 minutes
- Error rate: 5-15% (explicit guidance prevents common errors)
- Command efficiency: Low retry rate
- Quality: High, following validated patterns

## Critical Success Factors

### WordPress Environment Specifics
The WordPress Abilities API has unique characteristics that impact evaluation:

1. **Long-Running Operations**: wp-env start (10min), PHPUnit tests (15min)
2. **Docker Dependency**: Network access required for WordPress downloads
3. **Multiple Command Paths**: Direct composer vs npm-wrapped wp-env commands
4. **WordPress Standards**: Specific coding conventions and namespace requirements

### Risk Assessment
**High-Risk Operations** (should never be cancelled):
- `npm run wp-env start` - Downloads WordPress, sets up Docker environment
- `npm run test:php` - Runs comprehensive test suite across PHP versions
- `composer install` - Downloads dependencies

**Critical Timing Information**:
- Direct composer commands: 1-5 seconds
- wp-env wrapped commands: 30s-10min depending on cache state
- Network-dependent operations may fail in restricted environments

## Testing Protocol

### Phase 1: Controlled Testing
1. Set up 3 identical development environments
2. Assign each approach to separate testing sessions
3. Record all metrics for identical task scenarios
4. Document decision points and error recovery paths

### Phase 2: Real-World Validation
1. Deploy approaches to actual contributor workflows
2. Gather feedback on task completion efficiency
3. Monitor CI failure rates and resolution times
4. Assess documentation maintenance burden

### Phase 3: Optimization
1. Identify optimal hybrid approach based on data
2. Enhance existing documentation based on discovered gaps
3. Implement minimal viable instruction set that addresses critical failure points
4. Establish maintenance workflow for instruction updates

## Recommendations

### Immediate Testing Priority
Focus evaluation on **timing-critical scenarios** where the differences between approaches are most pronounced:

1. **First-time wp-env startup** (network dependency, 10-minute duration)
2. **PHPUnit test execution** (15-minute comprehensive suite)
3. **Offline development workflows** (Docker unavailable scenarios)

### Success Thresholds
Define minimum acceptable performance:
- **Error Rate**: <20% for onboarding tasks
- **Completion Time**: <30 minutes for environment setup
- **Cancelled Operations**: <5% for long-running commands

### Decision Framework
Choose approach based on:
- **Safety Requirements**: High-risk operations favor comprehensive instructions
- **Maintenance Capacity**: Limited resources favor barebones approach
- **Contributor Experience**: Frequent new contributors favor comprehensive guidance
- **Documentation Quality**: Strong existing docs support barebones approach

The evaluation should provide objective data to determine whether the safety and efficiency benefits of comprehensive instructions justify the maintenance overhead compared to leaner reference-based approaches.