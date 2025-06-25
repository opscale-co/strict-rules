# DDD Assumptions

This document outlines the key assumptions behind our application of Domain-Driven Design (DDD) principles within a Laravel-based architecture.

---

## 1. Leveraging Laravel

The main objective of DDD is to model the business domain as software objects. While DDD advocates for complete independence from frameworks, we have made intentional trade-offs by leveraging Laravel’s structure while maintaining the conceptual integrity of DDD.

This allows us to:

- Avoid over-engineering while still modeling the domain faithfully.
- Use Laravel's powerful tools to streamline development.
- Preserve domain intent without fragmenting the system.

The goal is not to build an over-architected solution, but to apply domain modeling effectively within a pragmatic Laravel environment.

---

## 2. Encouraging the Use of Packages

To simplify the application of domain rules and avoid a single monolithic domain, we enforce a division of logic into subdomains. This is achieved through modularization using packages.

Each subdomain is implemented as an independent package that:

- Handles a specific and concrete business responsibility.
- Encourages clear separation of concerns.
- Supports high decoupling and low coupling between modules.
- Simplifies the flow of logic by keeping each domain small and focused.

By structuring subdomains as standalone packages, we foster maintainability, scalability, and domain clarity across the project.

---

## 3. Managed DDD Concepts

This section provides an index of all DDD concepts implemented in this project, with their core purpose:

### 📋 Index

| Concept | Purpose | Rules |
|---------|-------------|-----------|
| **[Aggregates](./Aggregates/documentation.md)** | Ensure data consistency through single entry points | `ModelValidationRule`, `ParentChildTransactionRule` |
| **[Domain](./Domain/documentation.md)** | Keep business logic pure and declarative | `NoStatementsLogicRule` |
| **[Domain Services](./DomainServices/documentation.md)** | Coordinate complex operations across multiple entities | `ComplexLogicRule` |
| **[Entities](./Entities/documentation.md)** | Use ULIDs for globally unique, infrastructure-independent identity | `EnforceUlidsRule` |
| **[Repositories](./Repositories/documentation.md)** | Isolate persistence logic in dedicated traits | `EloquentRestrictionRule` |
| **[Subdomains](./Subdomains/documentation.md)** | Organize code into focused, package-oriented modules | `BaseNamespaceRule`, `EntityCountRule` |
| **[Value Objects](./ValueObjects/documentation.md)** | Implement immutable domain concepts using Laravel casts | `EnforceCastRule`, `NoAccesorMutatorRule` |