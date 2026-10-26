<?php

namespace App;

final class Pricing
{
    /**
     * Calculate a configured product price. This is the only place prices are
     * derived; the browser's numbers are ignored when an item is added to cart.
     *
     * @param array $product   Product row
     * @param array $selection ['groups' => [slug => valueId|array], 'dimensions' => [slug => ['w'=>..,'h'=>..,'d'=>..]]]
     * @param array|null $customer Customer row, or null for the current visitor
     */
    public static function quote(array $product, array $selection, ?array $customer = null): array
    {
        if ($customer === null) {
            $customer = current_customer();
        }

        $groups = Catalog::optionGroups((int) $product['id']);
        $base = (int) $product['base_price_paise'];
        $dimensionCharge = 0;
        $areaSqm = 0.0;
        $deltas = 0;
        $multBp = 0;
        $errors = [];
        $selectedGroups = [];
        $selectedDimensions = [];
        $summary = [];

        if (($product['product_type'] ?? 'simple') === 'configurable') {
            foreach ($groups as $group) {
                $slug = $group['slug'];

                if ($group['input_type'] === 'dimension') {
                    $raw = $selection['dimensions'][$slug] ?? [];
                    $w = (int) ($raw['w'] ?? $group['default_w'] ?? $group['min_value'] ?? 0);
                    $h = (int) ($raw['h'] ?? $group['default_h'] ?? $group['min_value'] ?? 0);
                    $d = (int) ($raw['d'] ?? $group['default_d'] ?? $group['min_value'] ?? 0);

                    $min = (int) ($group['min_value'] ?: 100);
                    $max = (int) ($group['max_value'] ?: 5000);
                    $w = max($min, min($max, $w));
                    $h = max($min, min($max, $h));
                    $d = max($min, min($max, $d));

                    $area = 2 * (($w * $h) + ($w * $d) + ($h * $d)) / 1000000;
                    $charge = (int) round($area * (int) $product['dim_rate_paise_sqm']);

                    $areaSqm += $area;
                    $dimensionCharge += $charge;
                    $selectedDimensions[] = [
                        'group_id' => (int) $group['id'],
                        'slug' => $slug,
                        'name' => $group['name'],
                        'w' => $w,
                        'h' => $h,
                        'd' => $d,
                        'area_sqm' => round($area, 4),
                        'charge_paise' => $charge,
                    ];
                    $summary[] = $group['name'] . ': ' . $w . ' × ' . $h . ' × ' . $d . ' mm';
                    continue;
                }

                $byId = [];
                foreach ($group['values'] as $value) {
                    $byId[(int) $value['id']] = $value;
                }

                $raw = $selection['groups'][$slug] ?? null;
                $ids = [];
                if (is_array($raw)) {
                    foreach ($raw as $id) {
                        $id = (int) $id;
                        if ($id > 0) {
                            $ids[] = $id;
                        }
                    }
                } elseif ($raw !== null && $raw !== '') {
                    $ids[] = (int) $raw;
                }

                if ($group['input_type'] === 'checkbox') {
                    if (!$ids && (int) $group['is_required'] === 1) {
                        $errors[] = 'Choose at least one option for ' . $group['name'] . '.';
                    }
                } else {
                    if (!$ids) {
                        $default = null;
                        foreach ($group['values'] as $value) {
                            if ((int) $value['is_default'] === 1) {
                                $default = $value;
                                break;
                            }
                        }
                        if ($default === null && $group['values']) {
                            $default = $group['values'][0];
                        }
                        if ($default !== null) {
                            $ids[] = (int) $default['id'];
                        } elseif ((int) $group['is_required'] === 1) {
                            $errors[] = 'Choose ' . $group['name'] . '.';
                        }
                    }
                    if (count($ids) > 1) {
                        $ids = [reset($ids)];
                    }
                }

                foreach ($ids as $id) {
                    if (!isset($byId[$id])) {
                        $errors[] = 'One of the selected ' . strtolower($group['name']) . ' options is no longer available.';
                        continue;
                    }
                    $value = $byId[$id];
                    $deltas += (int) $value['price_delta_paise'];
                    $multBp += (int) $value['price_multiplier_bp'];
                    $selectedGroups[] = [
                        'group_id' => (int) $group['id'],
                        'slug' => $slug,
                        'name' => $group['name'],
                        'value_id' => (int) $value['id'],
                        'value_label' => $value['label'],
                        'delta_paise' => (int) $value['price_delta_paise'],
                        'mult_bp' => (int) $value['price_multiplier_bp'],
                    ];
                    $summary[] = $group['name'] . ': ' . $value['label'];
                }
            }
        }

        $gross = $base + $dimensionCharge + $deltas;
        if ($multBp !== 0) {
            $gross = (int) round($gross * (10000 + $multBp) / 10000);
        }

        $discountBp = (int) ($customer['discount_bp'] ?? 0);
        $unit = $gross;
        if ($discountBp > 0) {
            $unit = (int) round($gross * (10000 - $discountBp) / 10000);
        }
        $unit = max(0, $unit);

        $options = [
            'groups' => $selectedGroups,
            'dimensions' => $selectedDimensions,
            'summary' => $summary,
            'base_price_paise' => $base,
            'dimension_charge_paise' => $dimensionCharge,
            'area_sqm' => round($areaSqm, 4),
            'gross_unit_paise' => $gross,
            'discount_bp' => $discountBp,
            'discount_paise' => max(0, $gross - $unit),
            'unit_price_paise' => $unit,
        ];

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'unit_price_paise' => $unit,
            'gross_unit_paise' => $gross,
            'discount_bp' => $discountBp,
            'discount_paise' => max(0, $gross - $unit),
            'options' => $options,
            'summary' => $summary,
        ];
    }

    /**
     * Stable hash for merging identical configurations in the cart.
     */
    public static function hash(int $productId, array $options): string
    {
        $signature = ['product' => $productId, 'groups' => [], 'dimensions' => []];

        foreach ($options['groups'] ?? [] as $group) {
            $signature['groups'][$group['slug']] = (int) $group['value_id'];
        }
        ksort($signature['groups']);

        foreach ($options['dimensions'] ?? [] as $dimension) {
            $signature['dimensions'][$dimension['slug']] = [
                (int) $dimension['w'],
                (int) $dimension['h'],
                (int) $dimension['d'],
            ];
        }
        ksort($signature['dimensions']);

        return sha1(json_encode($signature));
    }
}
