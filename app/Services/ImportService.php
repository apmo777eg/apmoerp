<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    use HandlesServiceErrors;

    protected array $errors = [];

    protected int $successCount = 0;

    protected int $failedCount = 0;

    /**
     * Get importable entities with module-aware product fields
     */
    public function getImportableEntities(?int $moduleId = null): array
    {
        $productColumns = $this->getProductColumnsForModule($moduleId);

        return [
            'products' => [
                'name' => __('Products'),
                'required_columns' => $productColumns['required'],
                'optional_columns' => $productColumns['optional'],
                'validation_rules' => $productColumns['validation'],
                'unique_columns' => ['sku', 'barcode'],
                'supports_modules' => true,
            ],
            'customers' => [
                'name' => __('Customers'),
                'required_columns' => ['name'],
                'optional_columns' => ['email', 'phone', 'address', 'city', 'country', 'tax_id', 'credit_limit', 'notes'],
                'validation_rules' => [
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:50',
                    'address' => 'nullable|string|max:500',
                    'credit_limit' => 'nullable|numeric|min:0',
                    'notes' => 'nullable|string|max:1000',
                ],
                'unique_columns' => ['email', 'phone'],
            ],
            'suppliers' => [
                'name' => __('Suppliers'),
                'required_columns' => ['name'],
                'optional_columns' => ['email', 'phone', 'address', 'city', 'country', 'tax_id', 'payment_terms', 'notes'],
                'validation_rules' => [
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:50',
                    'address' => 'nullable|string|max:500',
                    'notes' => 'nullable|string|max:1000',
                ],
                'unique_columns' => ['email', 'phone'],
            ],
            'employees' => [
                'name' => __('Employees'),
                'required_columns' => ['code', 'name'],
                'optional_columns' => ['email', 'phone', 'department', 'position', 'hire_date', 'salary', 'address', 'emergency_contact'],
                'validation_rules' => [
                    'code' => 'required|string|max:50',
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone' => 'nullable|string|max:50',
                    'department' => 'nullable|string|max:100',
                    'position' => 'nullable|string|max:100',
                    'hire_date' => 'nullable|date',
                    'salary' => 'nullable|numeric|min:0',
                ],
                'unique_columns' => ['email'],
            ],
            'expenses' => [
                'name' => __('Expenses'),
                'required_columns' => ['expense_date', 'amount', 'category'],
                'optional_columns' => ['description', 'reference', 'payment_method', 'notes'],
                'validation_rules' => [
                    'expense_date' => 'required|date',
                    'amount' => 'required|numeric|min:0',
                    'category' => 'required|string|max:255',
                    'description' => 'nullable|string|max:500',
                    'reference' => 'nullable|string|max:100',
                ],
                // Reference used for duplicate detection; if empty, expense_date+amount+category combo checked
                'unique_columns' => ['reference'],
            ],
            'incomes' => [
                'name' => __('Incomes'),
                'required_columns' => ['income_date', 'amount', 'category'],
                'optional_columns' => ['description', 'reference', 'payment_method', 'notes'],
                'validation_rules' => [
                    'income_date' => 'required|date',
                    'amount' => 'required|numeric|min:0',
                    'category' => 'required|string|max:255',
                    'description' => 'nullable|string|max:500',
                    'reference' => 'nullable|string|max:100',
                ],
                // Reference used for duplicate detection; if empty, income_date+amount+category combo checked
                'unique_columns' => ['reference'],
            ],
            'categories' => [
                'name' => __('Categories'),
                'required_columns' => ['name'],
                'optional_columns' => ['description', 'parent_id', 'is_active'],
                'validation_rules' => [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string|max:500',
                    'is_active' => 'nullable|boolean',
                ],
                'unique_columns' => ['name'],
            ],
            'sales' => [
                'name' => __('Sales Invoices'),
                'required_columns' => ['date', 'total', 'status'],
                'optional_columns' => ['reference', 'customer', 'subtotal', 'tax', 'discount', 'paid', 'due'],
                'validation_rules' => [
                    'reference' => 'nullable|string|max:50',
                    'date' => 'required|date',
                    'customer' => 'nullable|string|max:255',
                    'total' => 'required|numeric|min:0',
                    'status' => 'required|in:draft,posted,paid,cancelled',
                ],
                'unique_columns' => ['reference'],
            ],
            'purchases' => [
                'name' => __('Purchase Invoices'),
                'required_columns' => ['date', 'total', 'status'],
                'optional_columns' => ['reference', 'supplier', 'subtotal', 'tax', 'discount', 'paid', 'due'],
                'validation_rules' => [
                    'reference' => 'nullable|string|max:50',
                    'date' => 'required|date',
                    'supplier' => 'nullable|string|max:255',
                    'total' => 'required|numeric|min:0',
                    'status' => 'required|in:draft,posted,paid,cancelled',
                ],
                'unique_columns' => ['reference'],
            ],
        ];
    }

    /**
     * Get product columns dynamically based on module
     */
    protected function getProductColumnsForModule(?int $moduleId): array
    {
        $required = ['name'];
        $optional = ['sku', 'barcode', 'default_price', 'cost', 'min_stock', 'is_active', 'category_id', 'module_id', 'description', 'unit'];
        $validation = [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'default_price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:1000',
            'unit' => 'nullable|string|max:50',
        ];

        // Add module-specific fields if module is specified
        if ($moduleId) {
            $moduleFields = \App\Models\ModuleProductField::where('module_id', $moduleId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($moduleFields as $field) {
                $fieldKey = $field->field_key;

                // Add to optional columns
                $optional[] = $fieldKey;

                // Generate validation rule based on field type
                $validationRule = $this->getValidationRuleForField($field);

                if ($field->is_required) {
                    $required[] = $fieldKey;
                    // Remove 'nullable|' prefix correctly using str_replace
                    $validationRule = 'required|'.str_replace('nullable|', '', $validationRule);
                }

                $validation[$fieldKey] = $validationRule;
            }
        }

        return [
            'required' => $required,
            'optional' => array_unique($optional),
            'validation' => $validation,
        ];
    }

    /**
     * Generate validation rule based on field type
     */
    protected function getValidationRuleForField(\App\Models\ModuleProductField $field): string
    {
        return match ($field->field_type) {
            'number', 'decimal' => 'nullable|numeric',
            'date' => 'nullable|date',
            'datetime' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'url' => 'nullable|url|max:500',
            'checkbox' => 'nullable|boolean',
            'select', 'radio' => 'nullable|string|max:255',
            'multiselect' => 'nullable|string|max:1000',
            'textarea' => 'nullable|string|max:5000',
            default => 'nullable|string|max:255',
        };
    }

    /**
     * Get modules that support items/products
     */
    public function getModulesWithProducts(): array
    {
        return \App\Models\Module::where('is_active', true)
            ->where('supports_items', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'icon'])
            ->toArray();
    }

    public function getTemplateColumns(string $entityType, ?int $moduleId = null): array
    {
        $entities = $this->getImportableEntities($moduleId);
        if (! isset($entities[$entityType])) {
            return [];
        }

        $entity = $entities[$entityType];

        return array_merge($entity['required_columns'], $entity['optional_columns']);
    }

    public function generateTemplate(string $entityType, ?int $moduleId = null): ?string
    {
        return $this->handleServiceOperation(
            callback: function () use ($entityType, $moduleId) {
                $columns = $this->getTemplateColumns($entityType, $moduleId);
                if (empty($columns)) {
                    return null;
                }

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
                $sheet = $spreadsheet->getActiveSheet();

                // Write header row
                $col = 1;
                foreach ($columns as $column) {
                    $sheet->setCellValue([$col, 1], $column);
                    $col++;
                }

                // Style header
                $sheet->getStyle('1:1')->getFont()->setBold(true);

                // Auto-size columns
                foreach (range(1, count($columns)) as $colIndex) {
                    $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
                }

                $moduleSuffix = $moduleId ? "_module{$moduleId}" : '';
                $filename = "import_template_{$entityType}{$moduleSuffix}_".date('Y-m-d').'.xlsx';
                $path = 'imports/templates/'.$filename;

                Storage::disk('local')->makeDirectory('imports/templates');

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save(Storage::disk('local')->path($path));

                return $path;
            },
            operation: 'generateTemplate',
            context: ['entity_type' => $entityType, 'module_id' => $moduleId]
        );
    }

    public function import(string $entityType, string $filePath, array $options = []): array
    {
        $this->errors = [];
        $this->successCount = 0;
        $this->failedCount = 0;
        $moduleId = $options['module_id'] ?? null;

        return $this->handleServiceOperation(
            callback: function () use ($entityType, $filePath, $options, $moduleId) {
                $entities = $this->getImportableEntities($moduleId);
                if (! isset($entities[$entityType])) {
                    throw new \InvalidArgumentException("Unknown entity type: {$entityType}");
                }

                $entityConfig = $entities[$entityType];
                $updateExisting = $options['update_existing'] ?? false;
                $skipDuplicates = $options['skip_duplicates'] ?? true;
                $branchId = $options['branch_id'] ?? auth()->user()?->branch_id;

                // Use chunked reading to avoid memory issues with large files
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();

                // Get headers from first row
                $headerRow = $worksheet->rangeToArray('A1:'.$worksheet->getHighestColumn().'1')[0];
                $headers = array_map('strtolower', array_map('trim', $headerRow));

                $highestRow = $worksheet->getHighestRow();

                if ($highestRow < 2) {
                    return [
                        'success' => false,
                        'message' => __('File is empty or has no data rows'),
                        'imported' => 0,
                        'failed' => 0,
                        'errors' => [],
                    ];
                }

                // Process in chunks to reduce memory usage
                $chunkSize = 100;
                $totalRows = $highestRow - 1; // Exclude header row

                for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
                    $endRow = min($startRow + $chunkSize - 1, $highestRow);

                    DB::beginTransaction();

                    try {
                        // Read chunk
                        $range = 'A'.$startRow.':'.$worksheet->getHighestColumn().$endRow;
                        $rows = $worksheet->rangeToArray($range);

                        foreach ($rows as $index => $row) {
                            $rowNum = $startRow + $index;
                            $rowData = [];

                            foreach ($headers as $colIndex => $header) {
                                $rowData[$header] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : null;
                            }

                            // Skip empty rows
                            if (empty(array_filter($rowData))) {
                                continue;
                            }

                            // Validate row
                            $validator = Validator::make($rowData, $entityConfig['validation_rules']);
                            if ($validator->fails()) {
                                $this->errors[] = [
                                    'row' => $rowNum,
                                    'errors' => $validator->errors()->all(),
                                ];
                                $this->failedCount++;

                                continue;
                            }

                            // Import based on entity type
                            try {
                                $result = match ($entityType) {
                                    'products' => $this->importProduct($rowData, $branchId, $updateExisting, $skipDuplicates, $moduleId),
                                    'customers' => $this->importCustomer($rowData, $branchId, $updateExisting, $skipDuplicates),
                                    'suppliers' => $this->importSupplier($rowData, $branchId, $updateExisting, $skipDuplicates),
                                    default => false,
                                };

                                if ($result) {
                                    $this->successCount++;
                                } else {
                                    $this->failedCount++;
                                }
                            } catch (\Exception $e) {
                                $this->errors[] = [
                                    'row' => $rowNum,
                                    'errors' => [$e->getMessage()],
                                ];
                                $this->failedCount++;
                            }
                        }

                        DB::commit();

                        // Clear memory after each chunk
                        unset($rows);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Import chunk failed', [
                            'start_row' => $startRow,
                            'end_row' => $endRow,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }
                }

                // Clear spreadsheet from memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                // Log the import activity
                activity()
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'entity_type' => $entityType,
                        'imported' => $this->successCount,
                        'failed' => $this->failedCount,
                    ])
                    ->log("Imported {$this->successCount} {$entityType} records");

                return [
                    'success' => true,
                    'message' => __(':count records imported successfully', ['count' => $this->successCount]),
                    'imported' => $this->successCount,
                    'failed' => $this->failedCount,
                    'errors' => $this->errors,
                ];
            },
            operation: 'import',
            context: ['entity_type' => $entityType, 'file' => $filePath],
            defaultValue: [
                'success' => false,
                'message' => __('Import failed'),
                'imported' => 0,
                'failed' => 0,
                'errors' => [],
            ]
        );
    }

    protected function importProduct(array $data, ?int $branchId, bool $updateExisting, bool $skipDuplicates, ?int $moduleId = null): bool
    {
        $query = Product::query();

        // Check for existing product by SKU or barcode
        if (! empty($data['sku'])) {
            $existing = $query->where('sku', $data['sku'])->first();
        } elseif (! empty($data['barcode'])) {
            $existing = $query->where('barcode', $data['barcode'])->first();
        } else {
            $existing = null;
        }

        // Extract module-specific field values
        $moduleFieldValues = [];
        if ($moduleId) {
            $moduleFields = \App\Models\ModuleProductField::where('module_id', $moduleId)
                ->where('is_active', true)
                ->get();

            foreach ($moduleFields as $field) {
                if (isset($data[$field->field_key])) {
                    $moduleFieldValues[$field->field_key] = $data[$field->field_key];
                }
            }
        }

        if ($existing) {
            if ($skipDuplicates && ! $updateExisting) {
                return false;
            }
            if ($updateExisting) {
                $existing->fill($this->sanitizeProductData($data, $branchId, $moduleId));
                $existing->save();

                // Refresh to get updated module_id for setFieldValue
                $existing->refresh();

                // Update module field values (only if product has matching module_id)
                if ($moduleId && ! empty($moduleFieldValues) && $existing->module_id === $moduleId) {
                    foreach ($moduleFieldValues as $fieldKey => $value) {
                        $existing->setFieldValue($fieldKey, $value);
                    }
                }

                return true;
            }
        }

        $product = Product::create($this->sanitizeProductData($data, $branchId, $moduleId));

        // Save module field values for new product (refresh to ensure module_id is set)
        $product->refresh();
        if ($moduleId && ! empty($moduleFieldValues) && $product->module_id === $moduleId) {
            foreach ($moduleFieldValues as $fieldKey => $value) {
                $product->setFieldValue($fieldKey, $value);
            }
        }

        return true;
    }

    protected function importCustomer(array $data, ?int $branchId, bool $updateExisting, bool $skipDuplicates): bool
    {
        $existing = null;
        if (! empty($data['email'])) {
            $existing = Customer::where('email', $data['email'])->first();
        } elseif (! empty($data['phone'])) {
            $existing = Customer::where('phone', $data['phone'])->first();
        }

        if ($existing) {
            if ($skipDuplicates && ! $updateExisting) {
                return false;
            }
            if ($updateExisting) {
                $existing->fill($this->sanitizeCustomerData($data, $branchId));
                $existing->save();

                return true;
            }
        }

        Customer::create($this->sanitizeCustomerData($data, $branchId));

        return true;
    }

    protected function importSupplier(array $data, ?int $branchId, bool $updateExisting, bool $skipDuplicates): bool
    {
        $existing = null;
        if (! empty($data['email'])) {
            $existing = Supplier::where('email', $data['email'])->first();
        } elseif (! empty($data['phone'])) {
            $existing = Supplier::where('phone', $data['phone'])->first();
        }

        if ($existing) {
            if ($skipDuplicates && ! $updateExisting) {
                return false;
            }
            if ($updateExisting) {
                $existing->fill($this->sanitizeSupplierData($data, $branchId));
                $existing->save();

                return true;
            }
        }

        Supplier::create($this->sanitizeSupplierData($data, $branchId));

        return true;
    }

    protected function sanitizeProductData(array $data, ?int $branchId, ?int $moduleId = null): array
    {
        // V21-HIGH-07 Fix: Use 'status' column instead of 'is_active'
        // The Product model uses 'status' = 'active' (see scopeActive())
        $isActive = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return [
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            // V52-CRIT-01 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for prices
            'default_price' => decimal_float($data['default_price'] ?? 0, 4),
            'cost' => decimal_float($data['cost'] ?? 0, 4),
            'min_stock' => (int) ($data['min_stock'] ?? 0),
            'status' => $isActive ? 'active' : 'inactive', // V21-HIGH-07 Fix
            'branch_id' => $branchId,
            'category_id' => ! empty($data['category_id']) ? (int) $data['category_id'] : null,
            'module_id' => $moduleId ?: (! empty($data['module_id']) ? (int) $data['module_id'] : null),
        ];
    }

    protected function sanitizeCustomerData(array $data, ?int $branchId): array
    {
        return [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            // V52-CRIT-01 FIX: Use decimal_float() with scale 4 to match decimal:4 schema for credit limits
            'credit_limit' => decimal_float($data['credit_limit'] ?? 0, 4),
            'branch_id' => $branchId,
            'is_active' => true,
        ];
    }

    protected function sanitizeSupplierData(array $data, ?int $branchId): array
    {
        return [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'branch_id' => $branchId,
            'is_active' => true,
        ];
    }
}
