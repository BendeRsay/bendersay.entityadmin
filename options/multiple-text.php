<?php

/**
 * Используется в options.php
 */

$entities = unserialize(\Bitrix\Main\Config\Option::get($mid, $option['CODE']));
?>


<table id="<?= $option['CODE']; ?>_table" cellspacing=0 cellpadding=3>
    <?php
    $i = 0;

foreach ($entities as $entity):?>
        <?php
    $i++; ?>
        <tr>
            <td>
                <input
                        type="text"
                        name="<?= $option['CODE']; ?>[]"
                        id="<?= $option['CODE']; ?>_elem_<?= $i; ?>"
                        value="<?= $entity; ?>"
                        size="<?= $option['SETTINGS'][1]; ?>"
                >
            </td>
        </tr>
    <?php
endforeach; ?>
    <?php
if ($entities) {
    ++$i;
}
?>
    <tr>
        <td>
            <input
                    type="text"
                    name="<?= $option['CODE']; ?>[]"
                    id="<?= $option['CODE']; ?>_elem_<?= $i; ?>"
                    size="<?= $option['SETTINGS'][1]; ?>"
            >
        </td>
    </tr>
</table>
<input type=button id="more_button" value="<?= $option['SETTINGS'][2]; ?>" onclick="AddTableRow()">
<script>
    let numRows = 0;
    let inputName = '<?= $option['CODE']; ?>' + '[]';
    let idPrefix = '<?= $option['CODE']; ?>' + '_elem_';
    let size = <?= $option['SETTINGS'][1]; ?>;

    function AddTableRow() {
        let oTable = BX('<?= $option['CODE'];?>_table');
        numRows = oTable.rows.length;
        let oRow = oTable.insertRow(-1);
        let oCell = oRow.insertCell(0);
        oCell.innerHTML = '<input type="text" name="' + inputName + '" id="' + idPrefix + numRows + '" size="' + size + '">';
    }
</script>