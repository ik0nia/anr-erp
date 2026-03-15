# Raport Duplicate CSV - dosar_export.csv

Data generare: 15.03.2026 11:54

## Sumar

- Total randuri CSV: 4763
- CNP-uri unice: 4720
- CNP-uri duplicate: 32 (afecteaza 43 randuri in plus)

## Categorii de duplicate

### 1. Duplicate identice (acelasi CNP + acelasi dosar = inregistrari 100% duplicate)

Aceste randuri sunt pur si simplu duplicate exacte in export. Se pastreaza un singur rand.

| CNP | Dosar | Nume | Stare |
|-----|-------|------|-------|
| 2680408192497 | 252165 | HORVATH MARIA |  |
| 2581102051114 | 252479 | RESTEA FLORICA |  |
| 2641106052858 | 253118 | MURANYI AURICA |  |
| 2560420054696 | 252521 | MATE ANA |  |
| 1690704321094 | 252225 | HASEGAN NICOLAE |  |
| 1530514054711 | 254795 | ERDELI IOAN |  |
| 2481222052861 | 255264 | NAGY MARGARETA |  |
| 2561101054655 | 255549 | BARAR ANA |  |
| 5070122055061 | 256036 | BIRO ERIK |  |
| 2770112051092 | 256334 | MALITA MARIOARA | ACTIV |
| 1761016054678 | 256504 | KULCSAR ALEXIU |  |
| 2850612051154 | 256526 | COVACIU MARIA-ADINA |  |
| 2500726054661 | 256533 | KOVACS AURORA-GYONGYI |  |
| 2690711054724 | 256536 | PINTIS CORNELIA |  |
| 1710709054666 | 256541 | SZEKELY MARIUS-ANDREI |  |
| 2680304051091 | 256543 | MARCHIS SIMONA-LIANA |  |
| 2700307052850 | 256549 | SZABO EVA |  |
| 1360512054652 | 256554 | FILIP GHEORGHE |  |
| 1660123052862 | 256565 | VARGA PAVEL |  |
| 2761214323921 | 256568 | IORDACHE MIOARA |  |
| 2620817051094 | 253275 | BLAJ FLORICA |  |
| 2740216057050 | 253325 | ETVES AURICA | ACTIV |

**Total: 22 persoane cu intrari duplicate identice**

### 2. Acelasi CNP, dosare diferite (posibil transferuri sau erori)

Aceste persoane apar cu acelasi CNP dar dosare diferite. Poate fi un transfer de dosar sau o eroare de inregistrare.

| CNP | Dosare | Nume | Observatie |
|-----|--------|------|------------|
| 1390121052133 | 252534, 252334 | BUSE IOAN | Acelasi nume, dosare diferite |
| 2271116052852 | 253282, 255013 | GAL CORNELIA | Acelasi nume, dosare diferite |
| 2440325050012 | 253734, 256166 | CRACIUN EUGENIA / SPINU EUGENIA | Nume diferite! Posibil eroare |
| 2280125051105 | 254185, 255036 | TEGLAS GHIZELA | Acelasi nume, dosare diferite |
| 1471002050024 | 254211, 254828 | BOHUS FLORIAN | Acelasi nume, dosare diferite |
| 1320618054668 | 254411, 255402 | BORTIS FLORIAN | Acelasi nume, dosare diferite |
| 1761127057907 | 254426, 255728 | HERMAN IOAN | Acelasi nume, dosare diferite |
| 1760907054693 | 254489, 256548 | DEHELEAN GHEORGHE-CRISTIA / DEHELEN GHEORGHE-CRISTIAN | Nume diferite! Posibil eroare |
| 2710908050016 | 256299, 256488 | GAIDOS TUNDE-EMILIA | Acelasi nume, dosare diferite |

**Total: 9 persoane cu dosare diferite**

### 3. CNP placeholder (2147483647)

Aceste persoane au CNP-ul setat la valoarea maxima INT (2147483647) - un placeholder numeric, nu un CNP real.
Fiecare are dosar diferit, deci sunt persoane distincte fara CNP valid.

| Dosar | Nume | Stare |
|-------|------|-------|
| 255381 | CIUCLE ECATERINA |  |
| 255445 | MEHESZ IOLAN | EXPIRAT |
| 250983 | FAUR ANA |  |
| 251016 | VENTEL FLOARE |  |
| 251567 | ANTAL EMERIC-ALEXANDRU |  |
| 251658 | DEMETER AURELIA |  |
| 253166 | FURIC MILAN-GHEORGHE |  |
| 253331 | BLAGA ILEANA |  |
| 253699 | CABAU VASALIE |  |
| 253830 | ZANK FLORICA |  |
| 253884 | FERCHE ANA |  |
| 253989 | LEZEU MARIA |  |
| 253219 | DONEA VALERIU |  |

**Total: 13 persoane fara CNP valid**

## Decizie la import

- **Duplicate identice**: se pastreaza un singur rand (ultimul din CSV)
- **Dosare diferite**: se pastreaza dosarul cu numarul mai mare (cel mai recent)
- **CNP placeholder**: se importa cu CNP NULL, identificare doar dupa numarul de dosar
