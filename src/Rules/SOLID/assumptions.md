# SOLID Assumptions

This document outlines how we apply SOLID principles in a practical and pragmatic way within our codebase. Instead of enforcing full formal adherence through abstract syntax tree (AST) analysis, we focus on eliminating common code smells and encouraging clean, maintainable design.

---

## 1) Data Types
Since PHP is a dynamically typed language by default, some SOLID principles are harder to enforce strictly. Therefore, we assume the use of **strict typing** (`declare(strict_types=1)`) to facilitate class analysis, improve clarity, and reduce ambiguity in method signatures and property definitions.

---

## 2) Simplified Application
Applying SOLID principles fully using AST analysis can be complex. Instead, we aim to **avoid common code smells** that typically arise when SOLID principles are not followed. This pragmatic approach helps keep the codebase clean and maintainable without enforcing rules that are difficult to automate or evaluate in real-world scenarios.

