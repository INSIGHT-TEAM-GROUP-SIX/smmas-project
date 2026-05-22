<?php
// Stock Report - Green Inventory Theme

$stmt = $conn->query("
    SELECT 
        m.medicine_id, m.medicine_name, m.category, m.strength,
        m.unit_of_measure, m.reorder_level, m.unit_price,
        COALESCE(SUM(b.qty_remaining), 0) as current_stock, m.status
    FROM medicine m
    LEFT JOIN batch b ON m.medicine_id = b.medicine_id AND b.batch_status = 'Active'
    GROUP BY m.medicine_id, m.medicine_name, m.category, m.strength, m.unit_of_measure, m.reorder_level, m.unit_price, m.status
    ORDER BY current_stock ASC
");
$stock_data = $stmt->fetchAll();

$total_stock_value = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;
foreach($stock_data as $item) {
    $total_stock_value += $item['current_stock'] * $item['unit_price'];
    if($item['current_stock'] <= $item['reorder_level'] && $item['current_stock'] > 0) $low_stock_count++;
    if($item['current_stock'] == 0) $out_of_stock_count++;
}
?>

<!-- Stock Report - Green Theme -->
<div style="background: linear-gradient(135deg, #134e5e, #71b280); color: white; padding: 25px; border-radius: 10px; margin-bottom: 25px;">
    <h2 style="margin: 0; color: white;">?? INVENTORY STOCK REPORT</h2>
    <p style="margin: 5px 0 0; opacity: 0.8;">Current Stock Levels & Valuation</p>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
    <div style="background: #d4edda; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #28a745;">
        <div style="font-size: 32px; color: #155724;">??</div>
        <div style="font-size: 24px; font-weight: bold; color: #155724;">UGX <?php echo number_format($total_stock_value); ?></div>
        <div>Total Inventory Value</div>
    </div>
    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #ffc107;">
        <div style="font-size: 32px;">??</div>
        <div style="font-size: 24px; font-weight: bold; color: #856404;"><?php echo $low_stock_count; ?></div>
        <div>Low Stock Items</div>
    </div>
    <div style="background: #f8d7da; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #dc3545;">
        <div style="font-size: 32px;">?</div>
        <div style="font-size: 24px; font-weight: bold; color: #721c24;"><?php echo $out_of_stock_count; ?></div>
        <div>Out of Stock</div>
    </div>
</div>

<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden;">
        <thead>
            <tr style="background: #134e5e; color: white;">
                <th style="padding: 12px;">Medicine</th>
                <th style="padding: 12px;">Category</th>
                <th style="padding: 12px;">Current Stock</th>
                <th style="padding: 12px;">Reorder Level</th>
                <th style="padding: 12px;">Unit Price</th>
                <th style="padding: 12px;">Total Value</th>
                <th style="padding: 12px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($stock_data as $item): 
                $stock_percent = min(100, ($item['current_stock'] / $item['reorder_level']) * 100);
                $bar_color = $item['current_stock'] == 0 ? '#dc3545' : ($item['current_stock'] <= $item['reorder_level'] ? '#ffc107' : '#28a745');
            ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 10px;"><strong><?php echo $item['medicine_name']; ?></strong><br><small><?php echo $item['strength']; ?></small></td>
                <td style="padding: 10px;"><?php echo $item['category']; ?></td>
                <td style="padding: 10px;">
                    <div style="font-size: 18px; font-weight: bold; color: <?php echo $bar_color; ?>;"><?php echo $item['current_stock']; ?></div>
                    <div style="background: #ecf0f1; height: 6px; border-radius: 3px; width: 100px;">
                        <div style="background: <?php echo $bar_color; ?>; height: 6px; border-radius: 3px; width: <?php echo $stock_percent; ?>%;"></div>
                    </div>
                </td>
                <td style="padding: 10px;"><?php echo $item['reorder_level']; ?></td>
                <td style="padding: 10px;">UGX <?php echo number_format($item['unit_price']); ?></td>
                <td style="padding: 10px; font-weight: bold;">UGX <?php echo number_format($item['current_stock'] * $item['unit_price']); ?></td>
                <td style="padding: 10px;">
                    <?php if($item['current_stock'] == 0): ?>
                        <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 5px; font-size: 11px;">OUT OF STOCK</span>
                    <?php elseif($item['current_stock'] <= $item['reorder_level']): ?>
                        <span style="background: #ffc107; color: #856404; padding: 4px 8px; border-radius: 5px; font-size: 11px;">LOW STOCK</span>
                    <?php else: ?>
                        <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 5px; font-size: 11px;">IN STOCK</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>