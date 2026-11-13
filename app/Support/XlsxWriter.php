<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Generador mínimo de archivos .xlsx (Excel) sin dependencias externas.
 *
 * Arma a mano el paquete OpenXML: una sola hoja con encabezado en negritas,
 * fila superior congelada, autofiltro y ancho de columna calculado según el
 * contenido.
 */
class XlsxWriter
{
    /**
     * Construye el archivo en memoria y devuelve su contenido binario.
     *
     * @param  string  $sheetName  Nombre de la hoja (Excel lo limita a 31 caracteres)
     * @param  array<int, string>  $headers  Encabezados de la primera fila
     * @param  array<int, array<int, mixed>>  $rows  Filas con los valores de cada celda
     */
    public static function create(string $sheetName, array $headers, array $rows): string
    {
        $headers = array_values($headers);
        $rows = array_map('array_values', array_values($rows));

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRelationships());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationships());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheet($headers, $rows));

        $zip->close();

        $content = file_get_contents($path);
        @unlink($path);

        if ($content === false) {
            throw new RuntimeException('No se pudo leer el archivo Excel generado.');
        }

        return $content;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::escape(self::sanitizeSheetName($sheetName)).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'</fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private static function worksheet(array $headers, array $rows): string
    {
        $lastRow = count($rows) + 1;
        $lastColumn = self::columnName(max(count($headers), 1));

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0">'
            .'<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            .'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>';

        foreach (self::columnWidths($headers, $rows) as $index => $width) {
            $column = $index + 1;
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        $xml .= '</cols><sheetData><row r="1">';

        foreach ($headers as $index => $header) {
            $xml .= self::cell(self::columnName($index + 1).'1', $header, 1);
        }

        $xml .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            $xml .= '<row r="'.$rowNumber.'">';

            foreach ($row as $columnIndex => $value) {
                $xml .= self::cell(self::columnName($columnIndex + 1).$rowNumber, $value);
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData><autoFilter ref="A1:'.$lastColumn.$lastRow.'"/></worksheet>';
    }

    /**
     * Todas las celdas se escriben como texto para que Excel no altere
     * nombres ni teléfonos (por ejemplo, quitando ceros a la izquierda).
     */
    private static function cell(string $reference, mixed $value, ?int $style = null): string
    {
        $styleAttribute = $style === null ? '' : ' s="'.$style.'"';
        $text = self::escape((string) ($value ?? ''));

        return '<c r="'.$reference.'" t="inlineStr"'.$styleAttribute.'><is><t xml:space="preserve">'.$text.'</t></is></c>';
    }

    private static function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + $index % 26).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, int>
     */
    private static function columnWidths(array $headers, array $rows): array
    {
        $widths = array_map(fn ($header) => mb_strlen((string) $header), $headers);
        $count = count($headers);

        foreach ($rows as $row) {
            for ($index = 0; $index < $count; $index++) {
                $widths[$index] = max($widths[$index], mb_strlen((string) ($row[$index] ?? '')));
            }
        }

        return array_map(fn (int $width) => max(12, min(50, $width + 2)), $widths);
    }

    private static function sanitizeSheetName(string $name): string
    {
        $name = trim(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $name));

        return $name === '' ? 'Hoja1' : mb_substr($name, 0, 31);
    }

    private static function escape(string $value): string
    {
        // Los caracteres de control no son válidos en XML y rompen el archivo.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
