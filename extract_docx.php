<?php
$zip = new ZipArchive();
$file = 'C:\\Users\\Usuario\\Desktop\\Facultad\\01. Tutoria 2026\\02. Modulo2\\02. servicios\\07- Especificacion, Secuencia y Clase Reclamos.docx';
if ($zip->open($file) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    $text = $dom->textContent;
    // Limpiar espacios múltiples
    $text = preg_replace('/\s+/', ' ', $text);
    echo $text;
} else {
    echo "Error al abrir el archivo";
}
