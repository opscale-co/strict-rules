# Clean Assumptions

This document describes the core assumptions and design patterns used in our Laravel project, following Clean Architecture principles. It defines the structure, layers, communication flow, and logic organization to ensure clarity, modularity, and maintainability.

---

## 1. Project Structure

The folder structure follows a layered and modular design, where each component has a clear responsibility:

```
app|src/
├── 1 Console/
│   └── 1.1 Commands/
├── 2 Contracts/
├── 3 Events/
├── 4 Exceptions/
├── 5 Http/
│   ├── 5.1 Controllers/
│   │   └── 3.1.1 API/
│   ├── 5.2 Middleware/
│   ├── 5.3 Requests/
│   └── 5.4 Resources/
├── 6 Jobs/
├── 7 Listeners/
├── 8 Models/
│   ├── 8.1 Enums/
│   ├── 8.2 Repositories/
│   └── 8.3 ValueObjects/
├── 9 Notifications/
├── 10 Nova/
│   ├── 10.1 Actions/
│   ├── 10.2 Cards/
│   ├── 10.3 Dashboards/
│   ├── 10.4 Fields/
│   ├── 10.5 Filters/
│   ├── 10.6 Lenses/
│   ├── 10.7 Menus/
│   ├── 10.8 Metrics/
│   └── 10.9 Repeaters/
├── 11 Observers/
├── 12 Policies/
└── 13 Providers/
└── 14 Services/
    └── 14.1 Actions/ -> Laravel Actions
```

---

## 2. Project Layers

Each layer is responsible for a different type of logic and data handling:

| Layer          | Role                                                       |
| -------------- | ---------------------------------------------------------- |
| Representation | Models – represent domain entities                         |
| Communication  | Observers – notify and react to model changes              |
| Transformation | Services, Exceptions – apply business rules, handle errors |
| Orchestration  | Jobs, Notifications – coordinate processes and flows       |
| Interaction    | Console, Http, Nova, Policies – user/system interfaces     |

---

## 3. Layers Communication

- **Downward Communication**: Follows dependency injection; upper layers depend on abstractions of the lower ones.
- **Upward Communication**: Happens via Events and Listeners; a layer emits an event, and one or more listeners react independently.

This ensures loose coupling, clear data flow, and high testability.

---

## 4. Logic Handling

### Laravel Actions in Services

We adopt [Laravel Actions](https://github.com/lorisleiva/laravel-actions) inside the `Services/Actions` folder. Each Action represents a use case, encapsulating a single unit of business logic. These Actions can be reused across:

- Controllers
- Commands
- Jobs
- Listeners

This design promotes separation of concerns and avoids embedding business logic directly in framework components. Each layer remains focused on its role:

- **Controllers** call Actions to execute business logic.
- **Jobs** orchestrate Actions for asynchronous processes.
- **Commands** trigger Actions via the CLI.
- **Listeners** react to events by invoking the corresponding Action.

The objective of using Actions in this flexible way is to facilitate **upward communication** and prevent unnecessary wrapping or duplication of logic within these components.

This approach results in reusable, isolated, and testable units of logic.

---

By enforcing these assumptions, we ensure our application is well-structured, scalable, and easy to evolve over time.

