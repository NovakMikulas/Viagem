<?php

declare(strict_types=1);

namespace App\Import;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Generator;
use RuntimeException;
use XMLReader;
use ZipArchive;

class GmlParcelReader
{
    private const NAMESPACES = [
        'gml' => 'http://www.opengis.net/gml/3.2',
        'cp' => 'http://inspire.ec.europa.eu/schemas/cp/4.0',
        'cp-ext' => 'http://services.cuzk.cz/xsd/inspire/cp-ext/4.0',
        'xlink' => 'http://www.w3.org/1999/xlink',
    ];

    private const SRS_NAME_URL = 'http://www.opengis.net/def/crs/EPSG/0/5514';

    private string $cadastralUnitName = '';
    private ?string $cadastralUnitGeometryGml = null;
    private int $skippedAnalogueCount = 0;

    public function read(string $zipPath): Generator
    {
        $this->cadastralUnitName = '';
        $this->cadastralUnitGeometryGml = null;
        $this->skippedAnalogueCount = 0;

        $reader = new XMLReader();
        if (!$reader->open($this->findXmlInZip($zipPath))) {
            throw new RuntimeException('Nelze otevřít GML v ' . $zipPath);
        }

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->depth !== 2) {
                continue;
            }

            if ($reader->localName === 'CadastralParcel') {
                yield $this->parseParcel($reader->readOuterXml());
            } elseif ($reader->localName === 'CadastralZoning') {
                $this->parseCadastralZoning($reader->readOuterXml());
            } elseif ($reader->localName === 'AnalogueCadastralParcel') {
                $this->skippedAnalogueCount++;
            }
        }

        $reader->close();
    }

    public function getCadastralUnitName(): string
    {
        return $this->cadastralUnitName;
    }

    public function getCadastralUnitGeometryGml(): ?string
    {
        return $this->cadastralUnitGeometryGml;
    }

    public function getSkippedAnalogueCount(): int
    {
        return $this->skippedAnalogueCount;
    }

    private function findXmlInZip(string $zipPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Nelze otevřít ZIP ' . $zipPath);
        }

        $xmlName = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($extension === 'xml' || $extension === 'gml') {
                $xmlName = $name;
                break;
            }
        }
        $zip->close();

        if ($xmlName === null) {
            throw new RuntimeException('V ZIPu ' . $zipPath . ' není žádný GML soubor.');
        }

        return 'zip://' . realpath($zipPath) . '#' . $xmlName;
    }

    private function parseParcel(string $xml): array
    {
        $xpath = $this->createXPath($xml);
        $parcel = $xpath->document->documentElement;

        $gmlId = $parcel->getAttributeNS(self::NAMESPACES['gml'], 'id');

        $reference = $xpath->evaluate('string(cp:nationalCadastralReference)', $parcel);

        $landTypeUrl = $xpath->evaluate('string(cp-ext:landType/@xlink:href)', $parcel);

        $area = $xpath->evaluate('string(cp:areaValue)', $parcel);
        $validFrom = $xpath->evaluate('string(cp:beginLifespanVersion)', $parcel);

        $geometryGml = $this->extractGeometryGml($xpath, $parcel);
        if ($geometryGml === null) {
            throw new RuntimeException('Parcela ' . $gmlId . ' nemá geometrii.');
        }

        return [
            'id' => (int) substr($gmlId, strlen('CPX.')),
            'cadastral_unit_code' => (int) explode('-', $reference)[0],
            'label' => $xpath->evaluate('string(cp:label)', $parcel),
            'area' => $area === '' ? null : (int) $area,
            'land_type' => $landTypeUrl === '' ? null : basename($landTypeUrl),
            'valid_from' => $validFrom === '' ? null : $validFrom,
            'geometry_gml' => $geometryGml,
        ];
    }

    private function parseCadastralZoning(string $xml): void
    {
        $xpath = $this->createXPath($xml);
        $zoning = $xpath->document->documentElement;

        $this->cadastralUnitName = $xpath->evaluate('string(cp:label)', $zoning);
        $this->cadastralUnitGeometryGml = $this->extractGeometryGml($xpath, $zoning);
    }

    private function extractGeometryGml(DOMXPath $xpath, DOMElement $feature): ?string
    {
        $geometry = $xpath->query('cp:geometry/*', $feature)->item(0);
        if ($geometry === null) {
            return null;
        }

        $geometryGml = $xpath->document->saveXML($geometry);

        return str_replace(self::SRS_NAME_URL, 'EPSG:5514', $geometryGml);
    }

    private function createXPath(string $xml): DOMXPath
    {
        $document = new DOMDocument();
        $document->loadXML($xml);

        $xpath = new DOMXPath($document);
        foreach (self::NAMESPACES as $prefix => $uri) {
            $xpath->registerNamespace($prefix, $uri);
        }

        return $xpath;
    }
}
