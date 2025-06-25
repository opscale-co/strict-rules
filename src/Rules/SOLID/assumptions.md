# SOLID Assumptions

This document outlines how we apply SOLID principles in a practical and pragmatic way within our codebase. Instead of enforcing full formal adherence through abstract syntax tree (AST) analysis, we focus on eliminating common code smells and encouraging clean, maintainable design.

---

## 1) Data Types
Since PHP is a dynamically typed language by default, some SOLID principles are harder to enforce strictly. Therefore, we assume the use of **strict typing** (`declare(strict_types=1)`) to facilitate class analysis, improve clarity, and reduce ambiguity in method signatures and property definitions.

---

## 2) Simplified Application
Applying SOLID principles fully using AST analysis can be complex. Instead, we aim to **avoid common code smells** that typically arise when SOLID principles are not followed. This pragmatic approach helps keep the codebase clean and maintainable without enforcing rules that are difficult to automate or evaluate in real-world scenarios.

---

## 3. Managed SOLID Principles

This section provides an index of all SOLID principles implemented in this project, with their practical application:

### 📋 Index

| Concept | Purpose | Rules |
|-----------|---------|----------------|
| **[Single Responsibility](./SRP/documentation.md)** | Prevent bloated classes that violate SRP | `MaxLinesRule` |
| **[Open/Closed](./OCP/documentation.md)** | Avoid accidental method overrides that break OCP | `ConditionalOverrideRule` |
| **[Liskov Substitution](./LSP/documentation.md)** | Ensure substitutability by preserving parent behavior (LSP) | `ParentCallRule` |
| **[Interface Segregation](./ISP/documentation.md)** | Discourage fat interfaces through meaningful implementations (ISP) | `EnforceImplementationRule` |
| **[Dependency Inversion](./DIP/documentation.md)** | Promote testability through dependency inversion (DIP) | `DisallowInstantiationRule` |

