# softlinegeodb
Conjunto de datos actualizado de los municipios españoles, códigos postales e información geográfica, en **formato base de datos MySQL** con tablas optimizadas para consultas rápidas y uso fácil.

## Actualización de los datos
Actualización 2023. Los datos procesados están creados a partir de fuentes oficiales: INE y CNIG (IGN), tan pronto el INE publicó en Internet los datos de Enero de 2023 del Callejero de Censo Electoral, exactamente el día 9 de marzo de 2023. El procesado de datos se realizó el 26 de marzo, descargando de nuevo también los últimos datos del CNIG. Ver más información en el apartado [fuentes de datos](https://github.com/softline-informatica/softlinegeodb#fuentes-de-datos)

## Datos
Países|250|
|:---|:---|

|Municipios España|8.131|Códigos postales|11.053|
|:---|:---|:---|:---|

La organización territorial de España parte de 19 demarcaciones autónomas, cada una formada por una o varias provincias (hasta 52). Cada provincia se divide en un número variable de municipios (en total 8.131).​ Los municipios son las entidades territoriales básicas en esta organización, aunque existen otras entidades territoriales como agrupaciones de municipios o entidades de rango inferior al municipio, conocidas como "entidades locales menores".

|Autonomías|19|
|:---|:---|
||17 comunidades autónomas de las que 2 son archipiélagos: las islas Baleares y las Canarias<br />2 ciudades autónomas en África: Ceuta y Melilla|

|Provincias|52|Islas|11|
|:---|:---|:---|:---|
||49 sin islas<br />3 con islas<br />&nbsp;|<br /><br /><br />|0<br />4 islas Baleares (1 provincia)<br />7 islas Canarias (en 2 provincias)|

## Motivación
1. Disponer de una base de datos relacional en la que estén normalizados los datos de provincia y municipios españoles, usando IDs numéricos _compatibles_ con los oficiales y no números inventados con AUTOINCREMENT que es lo fácil.
2. Poder determinar a qué municipio oficial pertenece un código postal y cuántas calles comprende (un municipio puede tener uno o más códigos postales y existen municipios que comparten el mismo código postal).
3. Geoposicionar (con latitud/longitud) los municipios y tener otra información, como la isla a la que pertenece, provincia, comunidad autónoma, nº de habitantes, altitud...
4. Poder presentarle al usuario los municipios de la ISLA a la que se refiere, en lugar de como hace "todo el mundo": darle los municipios de todas las islas mezclados, solo porque son "de la misma provincia"...
5. Poder relacionar datos del INE y otras fuentes -geográficas o no- a partir de referencias consistentes y compatibles (ID municipio, ID provincia, a veces se tiene solo el código postal como dato fiable, etc.)

## Fuentes de datos
INE - Instituto Nacional de Estadística, que mantiene los datos de municipios / provincias / comunidades autónomas y el callejero del censo electoral con sus códigos postales.

CNIG - Centro Nacional de Información Geográfica, dirigido por el IGN (Instituto Geográfico Nacional), que mantiene diversos conjuntos de datos geográficos.

## Tablas
La versión de datos _mínima_ consta de 7 tablas:
![Tablas versión minimalista de softlinegeodb](/images/softlinegeodb_tables2023.png)

La versión de datos extra incluye datos procesados del callejero del INE y las entidades poblacionales adicionales e información geográfica extra del CNIG, todo asociado a los ID de municipio, pero su uso es más raro y ocupan mucho más espacio (son cientos de miles de filas).
![Tablas extra de la versión full de softlinegeodb](/images/softlinegeodb_tables-extra2023.png)

### softlinegeodb_ine_ccaa
Las 19 comunidades autónomas (con las ciudades autónomas de Ceuta y Melilla).

![Estructura de la tabla "ine_ccaa" (Comunidades autónomas españolas)](/images/softlinegeodb_ine_ccaa-struct.png)
![Datos de la tabla "ine_ccaa"](/images/softlinegeodb_ine_ccaa-data.png)

### softlinegeodb_ine_provincias
![Estructura de la tabla "ine_provincias" (Provincias españolas)](/images/softlinegeodb_ine_provincias-struct.png)

### softlinegeodb_ine_islas
Permite asociar las 11 islas de entidad en España con su provincia
![Estructura de la tabla "ine_islas" (Islas españolas)](/images/softlinegeodb_ine_islas-struct.png)

### softlinegeodb_ine_municipios

![Estructura de la tabla "ine_municipios" (Municipios de España)](/images/softlinegeodb_ine_municipios-struct.png)

### softlinegeodb_ine_municipios_cp
De esta tabla podemos extraer a qué municipio pertenece un código postal. Algunos códigos postales están compartidos entre 2 municipios o más.
![Estructura de la tabla "ine_municipios_cp" (Códigos postales a municipios españoles)](/images/softlinegeodb_ine_municipios_cp-struct.png)

### softlinegeodb_ine_municipios_geo

![Estructura de la tabla "ine_municipios_geo" (Información geográfica de los Municipios españoles)](/images/softlinegeodb_ine_municipios_geo-struct.png)

