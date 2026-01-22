# APMO ERP - Table Inventory

## 1. Table Counts (Exact)

| Category | Count |
|----------|-------|
| **Total Tables** | 188 |
| **Pivot Tables** | 15 |
| **Branch-owned Tables** | 143 |
| **Global Reference Tables** | 22 |
| **User-owned Tables** | 8 |

---

## 2. Complete Table List by Classification

### 2.1 Global Reference Tables (22)
These tables contain system-wide data not scoped to any branch.

| # | Table Name | Reason |
|---|------------|--------|
| 1 | branches | Root reference table for branch isolation |
| 2 | modules | System modules registry |
| 3 | permissions | Spatie permission definitions |
| 4 | roles | Spatie role definitions |
| 5 | currencies | ISO currency codes |
| 6 | currency_rates | Exchange rates between currencies |
| 7 | units_of_measure | Measurement units |
| 8 | vehicle_models | Reference for auto parts compatibility |
| 9 | ticket_priorities | Ticket priority levels |
| 10 | ticket_sla_policies | SLA policy definitions |
| 11 | ticket_categories | Ticket categorization |
| 12 | report_definitions | Report metadata registry |
| 13 | report_templates | Report template definitions |
| 14 | dashboard_widgets | Available widget definitions |
| 15 | cache | Laravel cache storage |
| 16 | cache_locks | Laravel cache locks |
| 17 | sessions | Laravel sessions |
| 18 | jobs | Laravel queue jobs |
| 19 | job_batches | Laravel job batches |
| 20 | failed_jobs | Laravel failed jobs |
| 21 | password_reset_tokens | Laravel password resets |
| 22 | personal_access_tokens | Laravel Sanctum tokens |

### 2.2 Branch-owned Tables (143)
These tables contain data scoped to a specific branch.

| # | Table Name | Category | Reason |
|---|------------|----------|--------|
| 1 | users | Core | User accounts belong to branches |
| 2 | module_settings | Config | Branch-specific module settings |
| 3 | module_custom_fields | Config | Branch-specific custom fields |
| 4 | module_fields | Config | Branch-specific field config |
| 5 | module_navigation | Config | Branch-specific navigation |
| 6 | module_operations | Config | Branch-specific operations |
| 7 | module_policies | Config | Branch-specific policies |
| 8 | module_product_fields | Config | Branch-specific product fields |
| 9 | taxes | Master | Tax rates per branch |
| 10 | warehouses | Master | Warehouses per branch |
| 11 | product_categories | Master | Product categories per branch |
| 12 | price_groups | Master | Price groups per branch |
| 13 | products | Master | Products per branch |
| 14 | product_variations | Master | Product variants per branch |
| 15 | product_price_tiers | Master | Price tiers per branch |
| 16 | product_field_values | Master | Custom field values per branch |
| 17 | customers | Master | Customers per branch |
| 18 | suppliers | Master | Suppliers per branch |
| 19 | stores | E-commerce | Online stores per branch |
| 20 | store_integrations | E-commerce | Store integration config |
| 21 | store_tokens | E-commerce | OAuth tokens per store |
| 22 | store_orders | E-commerce | Synced orders per branch |
| 23 | store_sync_logs | E-commerce | Sync history per store |
| 24 | product_store_mappings | E-commerce | Product mappings per store |
| 25 | fiscal_periods | Accounting | Fiscal periods per branch |
| 26 | accounts | Accounting | Chart of accounts per branch |
| 27 | account_mappings | Accounting | Account mapping rules |
| 28 | journal_entries | Accounting | Journal entries per branch |
| 29 | journal_entry_lines | Accounting | JE line items |
| 30 | bank_accounts | Banking | Bank accounts per branch |
| 31 | bank_reconciliations | Banking | Reconciliation records |
| 32 | bank_transactions | Banking | Bank transactions |
| 33 | cashflow_projections | Banking | Cash flow forecasts |
| 34 | expense_categories | Expenses | Expense categories per branch |
| 35 | income_categories | Income | Income categories per branch |
| 36 | expenses | Expenses | Expense records per branch |
| 37 | incomes | Income | Income records per branch |
| 38 | fixed_assets | Assets | Fixed assets per branch |
| 39 | asset_depreciations | Assets | Depreciation records |
| 40 | asset_maintenance_logs | Assets | Maintenance history |
| 41 | pos_sessions | Sales | POS sessions per branch |
| 42 | sales | Sales | Sales transactions |
| 43 | sale_items | Sales | Sale line items |
| 44 | sale_payments | Sales | Payment records |
| 45 | receipts | Sales | Receipt documents |
| 46 | deliveries | Sales | Delivery records |
| 47 | purchase_requisitions | Purchases | Requisitions per branch |
| 48 | purchase_requisition_items | Purchases | Requisition line items |
| 49 | supplier_quotations | Purchases | Supplier quotes |
| 50 | supplier_quotation_items | Purchases | Quote line items |
| 51 | purchases | Purchases | Purchase orders |
| 52 | purchase_items | Purchases | PO line items |
| 53 | purchase_payments | Purchases | Supplier payments |
| 54 | goods_received_notes | Purchases | GRN documents |
| 55 | grn_items | Purchases | GRN line items |
| 56 | inventory_batches | Inventory | Batch tracking |
| 57 | inventory_serials | Inventory | Serial tracking |
| 58 | stock_adjustments | Inventory | Stock adjustment headers |
| 59 | adjustment_items | Inventory | Adjustment line items |
| 60 | transfers | Inventory | Transfer headers |
| 61 | transfer_items | Inventory | Transfer line items |
| 62 | stock_transfers | Inventory | Stock transfer headers |
| 63 | stock_transfer_items | Inventory | Stock transfer lines |
| 64 | stock_transfer_approvals | Inventory | Transfer approvals |
| 65 | stock_transfer_documents | Inventory | Transfer documents |
| 66 | stock_transfer_history | Inventory | Transfer status history |
| 67 | inventory_transits | Inventory | In-transit inventory |
| 68 | stock_movements | Inventory | All stock movements (polymorphic) |
| 69 | return_notes | Returns | Return note headers |
| 70 | sales_returns | Returns | Sales return headers |
| 71 | sales_return_items | Returns | Sales return lines |
| 72 | purchase_returns | Returns | Purchase return headers |
| 73 | purchase_return_items | Returns | Purchase return lines |
| 74 | return_refunds | Returns | Refund records |
| 75 | credit_notes | Returns | Credit note documents |
| 76 | credit_note_applications | Returns | Credit applications |
| 77 | debit_notes | Returns | Debit note documents |
| 78 | shifts | HR | Work shift definitions |
| 79 | hr_employees | HR | Employee records |
| 80 | attendances | HR | Attendance records |
| 81 | leave_types | HR | Leave type definitions |
| 82 | leave_balances | HR | Employee leave balances |
| 83 | leave_accrual_rules | HR | Leave accrual rules |
| 84 | leave_requests | HR | Leave request records |
| 85 | leave_request_approvals | HR | Leave approvals |
| 86 | leave_adjustments | HR | Manual leave adjustments |
| 87 | leave_encashments | HR | Leave encashment records |
| 88 | leave_holidays | HR | Holiday calendar |
| 89 | payrolls | HR | Payroll records |
| 90 | properties | Rental | Property records |
| 91 | rental_units | Rental | Rental unit records |
| 92 | tenants | Rental | Tenant records |
| 93 | rental_contracts | Rental | Lease contracts |
| 94 | rental_periods | Rental | Billing periods |
| 95 | rental_invoices | Rental | Rental invoices |
| 96 | rental_payments | Rental | Rental payments |
| 97 | vehicles | Vehicles | Vehicle inventory |
| 98 | vehicle_contracts | Vehicles | Vehicle sales/lease |
| 99 | vehicle_payments | Vehicles | Vehicle payments |
| 100 | warranties | Vehicles | Warranty records |
| 101 | work_centers | Manufacturing | Production work centers |
| 102 | bills_of_materials | Manufacturing | BOM headers |
| 103 | bom_items | Manufacturing | BOM components |
| 104 | bom_operations | Manufacturing | BOM operations |
| 105 | production_orders | Manufacturing | Production orders |
| 106 | production_order_items | Manufacturing | PO line items |
| 107 | production_order_operations | Manufacturing | PO operations |
| 108 | manufacturing_transactions | Manufacturing | Production transactions |
| 109 | projects | Projects | Project records |
| 110 | project_milestones | Projects | Project milestones |
| 111 | project_tasks | Projects | Project tasks |
| 112 | project_expenses | Projects | Project expenses |
| 113 | project_time_logs | Projects | Time tracking |
| 114 | tickets | Support | Support tickets |
| 115 | ticket_replies | Support | Ticket messages |
| 116 | documents | Documents | Document records |
| 117 | document_tags | Documents | Tag definitions |
| 118 | document_versions | Documents | Version history |
| 119 | document_shares | Documents | Access control |
| 120 | document_activities | Documents | Activity log |
| 121 | attachments | Documents | File attachments (polymorphic) |
| 122 | media | Documents | Media library |
| 123 | notes | Documents | Notes (polymorphic) |
| 124 | workflow_definitions | Workflow | Workflow templates |
| 125 | workflow_rules | Workflow | Workflow rule config |
| 126 | workflow_instances | Workflow | Active workflows (polymorphic) |
| 127 | workflow_approvals | Workflow | Approval records |
| 128 | workflow_audit_logs | Workflow | Workflow audit trail |
| 129 | workflow_notifications | Workflow | Workflow notifications |
| 130 | alert_rules | Alerts | Alert configuration |
| 131 | alert_instances | Alerts | Triggered alerts |
| 132 | alert_recipients | Alerts | Alert recipients |
| 133 | anomaly_baselines | Alerts | Anomaly detection baselines |
| 134 | low_stock_alerts | Alerts | Low stock alerts |
| 135 | supplier_performance_metrics | Alerts | Supplier metrics |
| 136 | notifications | Activity | User notifications |
| 137 | login_activities | Activity | Login audit log |
| 138 | audit_logs | Activity | General audit log |
| 139 | activity_log | Activity | Activity log (Spatie) |
| 140 | system_settings | Config | System configuration |
| 141 | loyalty_settings | Loyalty | Loyalty program config |
| 142 | loyalty_transactions | Loyalty | Points transactions |
| 143 | installment_plans | Installments | Installment plan headers |

### 2.3 User-owned Tables (8)
These tables contain data belonging to specific users.

| # | Table Name | Reason |
|---|------------|--------|
| 1 | scheduled_reports | User-scheduled report jobs |
| 2 | saved_report_views | User-saved filter configs |
| 3 | export_layouts | User export preferences |
| 4 | user_dashboard_layouts | User dashboard config |
| 5 | user_dashboard_widgets | User widget placement |
| 6 | user_preferences | User preferences |
| 7 | user_favorites | User bookmarks |
| 8 | user_sessions | User session tracking |

### 2.4 Pivot Tables (15)
Many-to-many relationship tables.

| # | Table Name | Relationship |
|---|------------|--------------|
| 1 | branch_user | branches ↔ users |
| 2 | branch_modules | branches ↔ modules |
| 3 | branch_admins | branches ↔ users (admins) |
| 4 | branch_employee | branches ↔ hr_employees |
| 5 | employee_shifts | hr_employees ↔ shifts |
| 6 | document_tag | documents ↔ document_tags |
| 7 | product_compatibilities | products ↔ vehicle_models |
| 8 | task_dependencies | project_tasks ↔ project_tasks (self-ref) |
| 9 | model_has_permissions | morphable ↔ permissions |
| 10 | model_has_roles | morphable ↔ roles |
| 11 | role_has_permissions | roles ↔ permissions |
| 12 | installment_payments | installment_plans → payments |
| 13 | search_history | User search history |
| 14 | search_index | Full-text search index |
| 15 | widget_data_cache | Widget data cache |

---

## 3. Ordered Migration Table List (Dependency-Safe Topological Sort)

The following is the exact order in which tables must be created to satisfy all foreign key dependencies.

**Legend:**
- `(no deps)` = No foreign key dependencies
- `(deps: X, Y)` = Depends on tables X and Y
- `[CYCLE]` = Part of a circular dependency, handled with nullable FK or ALTER

### Migration Order

```
001. branches                           (no deps)
002. permissions                        (no deps)
003. roles                              (no deps)
004. modules                            (no deps)
005. currencies                         (no deps)
006. units_of_measure                   (deps: units_of_measure - self-ref, nullable)
007. vehicle_models                     (deps: vehicle_models - self-ref, nullable)
008. ticket_priorities                  (no deps)
009. ticket_sla_policies                (no deps)
010. ticket_categories                  (deps: ticket_categories - self-ref, nullable)
011. report_definitions                 (no deps)
012. report_templates                   (deps: report_definitions)
013. dashboard_widgets                  (no deps)
014. cache                              (no deps)
015. cache_locks                        (no deps)
016. sessions                           (no deps)
017. jobs                               (no deps)
018. job_batches                        (no deps)
019. failed_jobs                        (no deps)
020. password_reset_tokens              (no deps)
021. personal_access_tokens             (no deps - morphable)
022. users                              (deps: branches)
023. role_has_permissions               (deps: permissions, roles)
024. model_has_permissions              (deps: permissions)
025. model_has_roles                    (deps: roles)
026. branch_user                        (deps: branches, users)
027. branch_modules                     (deps: branches, modules)
028. branch_admins                      (deps: branches, users)
029. module_settings                    (deps: branches, modules)
030. module_custom_fields               (deps: modules)
031. module_fields                      (deps: modules)
032. module_navigation                  (deps: modules)
033. module_operations                  (deps: modules)
034. module_policies                    (deps: modules)
035. module_product_fields              (deps: modules)
036. currency_rates                     (deps: currencies)
037. taxes                              (deps: branches)
038. warehouses                         (deps: branches, users)
039. product_categories                 (deps: branches, product_categories - self-ref)
040. price_groups                       (deps: branches)
041. products                           (deps: branches, product_categories, taxes, units_of_measure, warehouses)
042. product_variations                 (deps: products)
043. product_price_tiers                (deps: products, price_groups)
044. product_field_values               (deps: products, module_product_fields)
045. product_compatibilities            (deps: products, vehicle_models)
046. customers                          (deps: branches, users, price_groups)
047. suppliers                          (deps: branches, users, currencies)
048. stores                             (deps: branches, warehouses)
049. store_integrations                 (deps: stores)
050. store_tokens                       (deps: stores)
051. store_orders                       (deps: branches, stores, customers)
052. store_sync_logs                    (deps: stores)
053. product_store_mappings             (deps: products, stores)
054. fiscal_periods                     (deps: branches)
055. accounts                           (deps: branches, accounts - self-ref, currencies)
056. account_mappings                   (deps: branches, accounts)
057. journal_entries                    (deps: branches, fiscal_periods, users)
058. journal_entry_lines                (deps: journal_entries, accounts)
059. bank_accounts                      (deps: branches, accounts, currencies)
060. bank_reconciliations               (deps: bank_accounts, users)
061. bank_transactions                  (deps: bank_accounts, bank_reconciliations)
062. cashflow_projections               (deps: branches, bank_accounts)
063. expense_categories                 (deps: branches, accounts)
064. income_categories                  (deps: branches, accounts)
065. expenses                           (deps: branches, expense_categories, suppliers, bank_accounts, users)
066. incomes                            (deps: branches, income_categories, customers, bank_accounts, users)
067. fixed_assets                       (deps: branches, accounts, warehouses, users)
068. asset_depreciations                (deps: fixed_assets, journal_entries)
069. asset_maintenance_logs             (deps: fixed_assets, users)
070. pos_sessions                       (deps: branches, users)
071. sales                              (deps: branches, customers, users, warehouses, pos_sessions, currencies)
072. sale_items                         (deps: sales, products, product_variations, taxes, warehouses)
073. sale_payments                      (deps: sales, users, bank_accounts)
074. receipts                           (deps: sales, users)
075. deliveries                         (deps: sales, users)
076. purchase_requisitions              (deps: branches, users, warehouses)
077. purchase_requisition_items         (deps: purchase_requisitions, products)
078. supplier_quotations                (deps: branches, suppliers, purchase_requisitions)
079. supplier_quotation_items           (deps: supplier_quotations, products)
080. purchases                          (deps: branches, suppliers, users, warehouses, currencies)
081. purchase_items                     (deps: purchases, products, product_variations, taxes)
082. purchase_payments                  (deps: purchases, users, bank_accounts)
083. goods_received_notes               (deps: branches, purchases, users, warehouses)
084. grn_items                          (deps: goods_received_notes, purchase_items, products)
085. inventory_batches                  (deps: branches, products, warehouses, purchases)
086. inventory_serials                  (deps: branches, products, warehouses, inventory_batches)
087. stock_adjustments                  (deps: branches, warehouses, users)
088. adjustment_items                   (deps: stock_adjustments, products, inventory_batches)
089. transfers                          (deps: branches, warehouses, users)
090. transfer_items                     (deps: transfers, products, inventory_batches)
091. stock_transfers                    (deps: branches, warehouses, users)
092. stock_transfer_items               (deps: stock_transfers, products, inventory_batches)
093. stock_transfer_approvals           (deps: stock_transfers, users)
094. stock_transfer_documents           (deps: stock_transfers, users)
095. stock_transfer_history             (deps: stock_transfers, users)
096. inventory_transits                 (deps: branches, products, warehouses, stock_transfers)
097. stock_movements                    (deps: branches, products, warehouses, inventory_batches, inventory_serials, users) [POLYMORPHIC]
098. return_notes                       (deps: branches, users)
099. sales_returns                      (deps: branches, sales, customers, users, warehouses)
100. sales_return_items                 (deps: sales_returns, sale_items, products)
101. purchase_returns                   (deps: branches, purchases, suppliers, users, warehouses)
102. purchase_return_items              (deps: purchase_returns, purchase_items, products)
103. return_refunds                     (deps: sales_returns, purchase_returns, users)
104. credit_notes                       (deps: branches, sales_returns, customers, users)
105. credit_note_applications           (deps: credit_notes, sales)
106. debit_notes                        (deps: branches, purchase_returns, suppliers, users)
107. shifts                             (deps: branches)
108. hr_employees                       (deps: branches, users, shifts)
109. branch_employee                    (deps: branches, hr_employees)
110. employee_shifts                    (deps: hr_employees, shifts)
111. attendances                        (deps: branches, hr_employees, shifts)
112. leave_types                        (deps: branches)
113. leave_balances                     (deps: hr_employees, leave_types)
114. leave_accrual_rules                (deps: branches, leave_types)
115. leave_requests                     (deps: hr_employees, leave_types, users)
116. leave_request_approvals            (deps: leave_requests, users)
117. leave_adjustments                  (deps: hr_employees, leave_types, users)
118. leave_encashments                  (deps: hr_employees, leave_types, users)
119. leave_holidays                     (deps: branches)
120. payrolls                           (deps: branches, hr_employees, users)
121. properties                         (deps: branches, users)
122. rental_units                       (deps: properties)
123. tenants                            (deps: branches, users)
124. rental_contracts                   (deps: branches, rental_units, tenants, users)
125. rental_periods                     (deps: rental_contracts)
126. rental_invoices                    (deps: rental_contracts, rental_periods, tenants)
127. rental_payments                    (deps: rental_invoices, users, bank_accounts)
128. vehicles                           (deps: branches, vehicle_models, warehouses, users)
129. vehicle_contracts                  (deps: branches, vehicles, customers, users)
130. vehicle_payments                   (deps: vehicle_contracts, users, bank_accounts)
131. warranties                         (deps: vehicles, customers, suppliers)
132. work_centers                       (deps: branches, warehouses, users)
133. bills_of_materials                 (deps: branches, products, users)
134. bom_items                          (deps: bills_of_materials, products, units_of_measure)
135. bom_operations                     (deps: bills_of_materials, work_centers)
136. production_orders                  (deps: branches, bills_of_materials, products, warehouses, users)
137. production_order_items             (deps: production_orders, products, inventory_batches)
138. production_order_operations        (deps: production_orders, bom_operations, work_centers, users)
139. manufacturing_transactions         (deps: production_orders, products, warehouses, users)
140. projects                           (deps: branches, customers, users)
141. project_milestones                 (deps: projects)
142. project_tasks                      (deps: projects, project_milestones, users, project_tasks - self-ref)
143. task_dependencies                  (deps: project_tasks)
144. project_expenses                   (deps: projects, users, expense_categories)
145. project_time_logs                  (deps: projects, project_tasks, users)
146. tickets                            (deps: branches, customers, users, ticket_categories, ticket_priorities, ticket_sla_policies)
147. ticket_replies                     (deps: tickets, users)
148. document_tags                      (deps: branches)
149. documents                          (deps: branches, users, documents - self-ref)
150. document_tag                       (deps: documents, document_tags)
151. document_versions                  (deps: documents, users)
152. document_shares                    (deps: documents, users)
153. document_activities                (deps: documents, users)
154. attachments                        (deps: branches, users) [POLYMORPHIC]
155. media                              (deps: branches) [POLYMORPHIC - Spatie Media Library]
156. notes                              (deps: users) [POLYMORPHIC]
157. workflow_definitions               (deps: branches, users)
158. workflow_rules                     (deps: workflow_definitions)
159. workflow_instances                 (deps: workflow_definitions, users) [POLYMORPHIC]
160. workflow_approvals                 (deps: workflow_instances, users)
161. workflow_audit_logs                (deps: workflow_instances, users)
162. workflow_notifications             (deps: workflow_instances, users)
163. alert_rules                        (deps: branches, users)
164. alert_instances                    (deps: alert_rules, users)
165. alert_recipients                   (deps: alert_rules, users)
166. anomaly_baselines                  (deps: branches)
167. low_stock_alerts                   (deps: branches, products, warehouses)
168. supplier_performance_metrics       (deps: branches, suppliers)
169. scheduled_reports                  (deps: users, report_definitions)
170. saved_report_views                 (deps: users, report_definitions)
171. export_layouts                     (deps: users)
172. user_dashboard_layouts             (deps: users)
173. user_dashboard_widgets             (deps: user_dashboard_layouts, dashboard_widgets)
174. user_preferences                   (deps: users)
175. user_favorites                     (deps: users) [POLYMORPHIC]
176. user_sessions                      (deps: users)
177. notifications                      (deps: users) [POLYMORPHIC - Laravel Notifications]
178. login_activities                   (deps: users)
179. search_history                     (deps: users)
180. search_index                       (deps: branches)
181. audit_logs                         (deps: users) [POLYMORPHIC]
182. activity_log                       (deps: users) [POLYMORPHIC - Spatie Activity Log]
183. system_settings                    (deps: branches)
184. loyalty_settings                   (deps: branches)
185. loyalty_transactions               (deps: branches, customers, sales, users)
186. installment_plans                  (deps: branches, sales, customers, users)
187. installment_payments               (deps: installment_plans, users, bank_accounts)
188. widget_data_cache                  (deps: user_dashboard_widgets)
```

---

## 4. Circular Dependency Handling

The following tables have self-referencing or circular foreign keys. These are handled by making the FK column nullable and adding the constraint after table creation:

| Table | FK Column | Reference | Strategy |
|-------|-----------|-----------|----------|
| branches | parent_id | branches.id | Nullable self-reference |
| units_of_measure | base_unit_id | units_of_measure.id | Nullable self-reference |
| vehicle_models | parent_id | vehicle_models.id | Nullable self-reference |
| product_categories | parent_id | product_categories.id | Nullable self-reference |
| ticket_categories | parent_id | ticket_categories.id | Nullable self-reference |
| accounts | parent_id | accounts.id | Nullable self-reference |
| project_tasks | parent_id | project_tasks.id | Nullable self-reference |
| documents | parent_id | documents.id | Nullable self-reference (folder hierarchy) |

All self-referencing FKs are nullable, allowing insertion without circular dependency issues.

---

## 5. Polymorphic Tables

These tables use Laravel's polymorphic relationships with `{name}_id` and `{name}_type` columns:

| Table | Morph Columns | Used By |
|-------|---------------|---------|
| stock_movements | reference_type, reference_id | Sales, Purchases, Returns, Adjustments, Transfers |
| attachments | attachable_type, attachable_id | Any entity with attachments |
| notes | notable_type, notable_id | Any entity with notes |
| workflow_instances | entity_type, entity_id | Any entity with workflow |
| media | model_type, model_id | Spatie Media Library |
| model_has_permissions | model_type, model_id | Spatie Permissions |
| model_has_roles | model_type, model_id | Spatie Permissions |
| notifications | notifiable_type, notifiable_id | Laravel Notifications |
| user_favorites | favoritable_type, favoritable_id | User bookmarks |
| audit_logs | auditable_type, auditable_id | Audit trail |
| activity_log | subject_type, subject_id | Spatie Activity Log |
| personal_access_tokens | tokenable_type, tokenable_id | Laravel Sanctum |

---

## 6. Migration File Mapping

Each migration file creates tables in dependency order:

| Order | Migration File | Tables Created |
|-------|----------------|----------------|
| 1 | 2026_01_01_000001_create_branches_table.php | branches |
| 2 | 2026_01_01_000002_create_users_table.php | users |
| 3 | 2026_01_01_000003_create_permission_tables.php | permissions, roles, model_has_permissions, model_has_roles, role_has_permissions |
| 4 | 2026_01_01_000004_create_modules_table.php | modules |
| 5 | 2026_01_01_000005_create_branch_pivot_tables.php | branch_user, branch_modules, branch_admins |
| 6 | 2026_01_01_000006_create_module_configuration_tables.php | module_settings, module_custom_fields, module_fields, module_navigation, module_operations, module_policies, module_product_fields |
| 7 | 2026_01_02_000001_create_currencies_units_tables.php | currencies, currency_rates, units_of_measure |
| 8 | 2026_01_02_000002_create_taxes_table.php | taxes |
| 9 | 2026_01_02_000003_create_warehouses_table.php | warehouses |
| 10 | 2026_01_02_000004_create_product_categories_price_groups_tables.php | product_categories, price_groups |
| 11 | 2026_01_02_000005_create_products_table.php | products, product_variations, product_price_tiers, product_field_values |
| 12 | 2026_01_02_000006_create_vehicle_models_compatibilities_tables.php | vehicle_models, product_compatibilities |
| 13 | 2026_01_02_000007_create_customers_suppliers_tables.php | customers, suppliers |
| 14 | 2026_01_02_000008_create_stores_tables.php | stores, store_integrations, store_tokens, store_orders, store_sync_logs, product_store_mappings |
| 15 | 2026_01_03_000001_create_accounting_tables.php | fiscal_periods, accounts, account_mappings, journal_entries, journal_entry_lines |
| 16 | 2026_01_03_000002_create_banking_tables.php | bank_accounts, bank_reconciliations, bank_transactions, cashflow_projections |
| 17 | 2026_01_03_000003_create_expense_income_tables.php | expense_categories, income_categories, expenses, incomes |
| 18 | 2026_01_03_000004_create_fixed_assets_tables.php | fixed_assets, asset_depreciations, asset_maintenance_logs |
| 19 | 2026_01_04_000001_create_sales_tables.php | pos_sessions, sales, sale_items, sale_payments, receipts, deliveries |
| 20 | 2026_01_04_000002_create_purchases_tables.php | purchase_requisitions, purchase_requisition_items, supplier_quotations, supplier_quotation_items, purchases, purchase_items, purchase_payments, goods_received_notes, grn_items |
| 21 | 2026_01_04_000003_create_inventory_tables.php | inventory_batches, inventory_serials, stock_adjustments, adjustment_items, transfers, transfer_items, stock_transfers, stock_transfer_items, stock_transfer_approvals, stock_transfer_documents, stock_transfer_history, inventory_transits, stock_movements |
| 22 | 2026_01_04_000004_create_returns_tables.php | return_notes, sales_returns, sales_return_items, purchase_returns, purchase_return_items, return_refunds, credit_notes, credit_note_applications, debit_notes |
| 23 | 2026_01_05_000001_create_hr_payroll_tables.php | shifts, hr_employees, employee_shifts, branch_employee, attendances, leave_types, leave_balances, leave_accrual_rules, leave_requests, leave_request_approvals, leave_adjustments, leave_encashments, leave_holidays, payrolls |
| 24 | 2026_01_05_000002_create_rental_tables.php | properties, rental_units, tenants, rental_contracts, rental_periods, rental_invoices, rental_payments |
| 25 | 2026_01_05_000003_create_vehicle_tables.php | vehicles, vehicle_contracts, vehicle_payments, warranties |
| 26 | 2026_01_05_000004_create_manufacturing_tables.php | work_centers, bills_of_materials, bom_items, bom_operations, production_orders, production_order_items, production_order_operations, manufacturing_transactions |
| 27 | 2026_01_05_000005_create_project_tables.php | projects, project_milestones, project_tasks, task_dependencies, project_expenses, project_time_logs |
| 28 | 2026_01_06_000001_create_ticket_tables.php | ticket_priorities, ticket_sla_policies, ticket_categories, tickets, ticket_replies |
| 29 | 2026_01_06_000002_create_document_tables.php | document_tags, documents, document_tag, document_versions, document_shares, document_activities, attachments, media, notes |
| 30 | 2026_01_06_000003_create_workflow_tables.php | workflow_definitions, workflow_rules, workflow_instances, workflow_approvals, workflow_audit_logs, workflow_notifications |
| 31 | 2026_01_06_000004_create_alert_tables.php | alert_rules, alert_instances, alert_recipients, anomaly_baselines, low_stock_alerts, supplier_performance_metrics |
| 32 | 2026_01_07_000001_create_reporting_tables.php | report_definitions, report_templates, scheduled_reports, saved_report_views, export_layouts |
| 33 | 2026_01_07_000002_create_dashboard_tables.php | dashboard_widgets, user_dashboard_layouts, user_dashboard_widgets, widget_data_cache |
| 34 | 2026_01_07_000003_create_user_activity_tables.php | user_preferences, user_favorites, user_sessions, login_activities, search_history, search_index, notifications, system_settings, audit_logs, activity_log |
| 35 | 2026_01_07_000004_create_loyalty_installment_tables.php | loyalty_settings, loyalty_transactions, installment_plans, installment_payments |
| 36 | 2026_01_08_000001_create_laravel_framework_tables.php | cache, cache_locks, sessions, jobs, job_batches, failed_jobs, password_reset_tokens, personal_access_tokens |

---

## 7. Verification Checklist

- [x] Total tables: 188
- [x] All tables listed (no omissions)
- [x] Pivot tables identified: 15
- [x] Branch-owned tables: 143
- [x] Global tables: 22
- [x] User-owned tables: 8
- [x] Topological order verified (no FK references before table exists)
- [x] Self-referencing FKs handled with nullable columns
- [x] Polymorphic tables documented
- [x] Migration file order documented
