
import java.util.*;
import java.io.*;
import java.time.*;

public class Main {

    static Scanner sc = new Scanner(System.in);
    static Map<String, String> users = new HashMap<>();
    static Map<String, List<Transaction>> accounts = new HashMap<>();
    static Map<String, Set<String>> categories = new HashMap<>();
    static Map<String, Double> budgets = new HashMap<>();
    static Map<String, List<String>> logs = new HashMap<>();

    static String currentUser;
    static int nextId = 1;

    static class Transaction {
        int id;
        String type;
        String category;
        double amount;
        LocalDate date;

        Transaction(String type, String category, double amount) {
            this.id = nextId++;
            this.type = type;
            this.category = category;
            this.amount = amount;
            this.date = LocalDate.now();
        }

        public String toString() {
            return id + " | " + type + " | " + category
                    + " | " + amount + " KZT | " + date;
        }
    }

    public static void main(String[] args) {

        while (true) {
            System.out.println("\n===== FINPILOT =====");
            System.out.println("1. Register");
            System.out.println("2. Login");
            System.out.println("0. Exit");

            switch (number()) {
                case 1 -> register();
                case 2 -> login();
                case 0 -> {
                    return;
                }
                default -> System.out.println("Invalid choice");
            }
        }
    }

    // US-01: Registration
    static void register() {
        System.out.print("Username: ");
        String name = sc.nextLine();

        System.out.print("Password: ");
        String password = sc.nextLine();

        if (name.isBlank() || password.length() < 6) {
            System.out.println("Password: minimum 6 characters");
            return;
        }

        if (users.containsKey(name)) {
            System.out.println("User already exists");
            return;
        }

        users.put(name, password);
        accounts.put(name, new ArrayList<>());
        categories.put(name, new LinkedHashSet<>(
                List.of("Salary", "Food", "Transport", "Other")));
        logs.put(name, new ArrayList<>());

        System.out.println("Registration successful");
    }

    // US-02: Login
    static void login() {
        System.out.print("Username: ");
        String name = sc.nextLine();

        System.out.print("Password: ");
        String password = sc.nextLine();

        if (!password.equals(users.get(name))) {
            System.out.println("Incorrect username or password");
            return;
        }

        currentUser = name;
        log("LOGIN");
        dashboard();
    }

    static void dashboard() {
        while (true) {
            System.out.println("\n===== FINPILOT DASHBOARD =====");
            System.out.println("User: " + currentUser);
            System.out.println("1. Add income");
            System.out.println("2. Add expense");
            System.out.println("3. Transactions");
            System.out.println("4. Edit transaction");
            System.out.println("5. Delete transaction");
            System.out.println("6. Manage categories");
            System.out.println("7. Set budget");
            System.out.println("8. Statistics");
            System.out.println("9. Search transactions");
            System.out.println("10. Export report");
            System.out.println("11. Activity logs");
            System.out.println("0. Logout");

            switch (number()) {
                case 1 -> addTransaction("Income");
                case 2 -> addTransaction("Expense");
                case 3 -> showTransactions();
                case 4 -> editTransaction();
                case 5 -> deleteTransaction();
                case 6 -> manageCategories();
                case 7 -> setBudget();
                case 8 -> statistics();
                case 9 -> search();
                case 10 -> exportReport();
                case 11 -> showLogs();
                case 0 -> {
                    log("LOGOUT");
                    currentUser = null;
                    return;
                }
                default -> System.out.println("Invalid choice");
            }
        }
    }

    static List<Transaction> transactions() {
        return accounts.get(currentUser);
    }

    // US-03, US-04: Income and expense
    static void addTransaction(String type) {
        System.out.println("Categories: " + categories.get(currentUser));

        System.out.print("Category: ");
        String category = sc.nextLine();

        if (!categories.get(currentUser).contains(category)) {
            System.out.println("Category not found");
            return;
        }

        System.out.print("Amount: ");
        double amount = amount();

        if (amount <= 0) {
            System.out.println("Invalid amount");
            return;
        }

        transactions().add(new Transaction(type, category, amount));
        log("ADD " + type);

        System.out.println("Transaction saved");
        checkBudget();
    }

    static void showTransactions() {
        if (transactions().isEmpty()) {
            System.out.println("No transactions");
        }

        for (Transaction t : transactions()) {
            System.out.println(t);
        }
    }

    // US-05: Edit and delete
    static void editTransaction() {
        showTransactions();

        System.out.print("Transaction ID: ");
        int id = number();

        for (Transaction t : transactions()) {
            if (t.id == id) {
                System.out.print("New amount: ");
                double value = amount();

                if (value <= 0) {
                    System.out.println("Invalid amount");
                    return;
                }

                t.amount = value;
                log("EDIT transaction " + id);
                System.out.println("Updated");
                checkBudget();
                return;
            }
        }

        System.out.println("Transaction not found");
    }

    static void deleteTransaction() {
        showTransactions();

        System.out.print("Transaction ID: ");
        int id = number();

        boolean removed = transactions().removeIf(t -> t.id == id);

        if (removed) {
            log("DELETE transaction " + id);
            System.out.println("Deleted");
        } else {
            System.out.println("Transaction not found");
        }
    }

    // US-06: Category management
    static void manageCategories() {
        Set<String> list = categories.get(currentUser);

        System.out.println("Categories: " + list);
        System.out.println("1. Add category");
        System.out.println("2. Delete category");
        System.out.println("0. Back");

        int choice = number();

        if (choice == 0) return;

        System.out.print("Category name: ");
        String name = sc.nextLine();

        if (choice == 1 && !name.isBlank()) {
            list.add(name);
            log("ADD category " + name);
        } else if (choice == 2) {
            boolean used = transactions().stream()
                    .anyMatch(t -> t.category.equals(name));

            if (used) {
                System.out.println("Category is in use");
                return;
            }

            list.remove(name);
            log("DELETE category " + name);
        }

        System.out.println("Categories: " + list);
    }

    // US-07: Budget limit
    static void setBudget() {
        System.out.print("Monthly budget: ");
        double budget = amount();

        if (budget <= 0) {
            System.out.println("Invalid budget");
            return;
        }

        budgets.put(currentUser, budget);
        log("SET budget");
        System.out.println("Budget saved");
        checkBudget();
    }

    // US-08: Budget notification
    static void checkBudget() {
        double budget = budgets.getOrDefault(currentUser, 0.0);

        double expenses = transactions().stream()
                .filter(t -> t.type.equals("Expense"))
                .filter(t -> YearMonth.from(t.date)
                        .equals(YearMonth.now()))
                .mapToDouble(t -> t.amount)
                .sum();

        if (budget > 0 && expenses >= budget) {
            System.out.println("WARNING: Budget limit reached!");
        } else if (budget > 0 && expenses >= budget * 0.8) {
            System.out.println("WARNING: 80% of budget used!");
        }
    }

    // US-09: Balance and statistics
    static void statistics() {
        double income = transactions().stream()
                .filter(t -> t.type.equals("Income"))
                .mapToDouble(t -> t.amount)
                .sum();

        double expense = transactions().stream()
                .filter(t -> t.type.equals("Expense"))
                .mapToDouble(t -> t.amount)
                .sum();

        System.out.println("\n===== STATISTICS =====");
        System.out.println("Income: " + income + " KZT");
        System.out.println("Expense: " + expense + " KZT");
        System.out.println("Balance: " + (income - expense) + " KZT");
        System.out.println("Budget: "
                + budgets.getOrDefault(currentUser, 0.0) + " KZT");

        log("VIEW statistics");
        checkBudget();
    }

    // US-10: Search and filter
    static void search() {
        System.out.print("Category, type or date: ");
        String query = sc.nextLine().toLowerCase();

        for (Transaction t : transactions()) {
            if (t.toString().toLowerCase().contains(query)) {
                System.out.println(t);
            }
        }

        log("SEARCH transactions");
    }

    // US-11: Export CSV report
    static void exportReport() {
        try (PrintWriter writer = new PrintWriter(
                "FinPilot_Report.csv", "UTF-8")) {

            writer.println("ID,Type,Category,Amount,Date");

            for (Transaction t : transactions()) {
                String category = "\"" 
                        + t.category.replace("\"", "\"\"") + "\"";

                writer.println(t.id + "," + t.type + ","
                        + category + "," + t.amount + "," + t.date);
            }

            log("EXPORT report");
            System.out.println("FinPilot_Report.csv created");

        } catch (IOException e) {
            System.out.println("Export error");
        }
    }

    // US-12: Activity logging prototype
    static void log(String action) {
        logs.get(currentUser).add(
                LocalDateTime.now() + " | " + action);
    }

    static void showLogs() {
        for (String entry : logs.get(currentUser)) {
            System.out.println(entry);
        }
    }

    static int number() {
        try {
            return Integer.parseInt(sc.nextLine());
        } catch (NumberFormatException e) {
            return -1;
        }
    }

    static double amount() {
        try {
            double value = Double.parseDouble(sc.nextLine());
            return Double.isFinite(value) ? value : -1;
        } catch (NumberFormatException e) {
            return -1;
        }
    }
}
