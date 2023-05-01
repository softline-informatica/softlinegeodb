# softlinegeodb
Conjunto de datos actualizado de los municipios españoles, códigos postales e información geográfica, en formato base de datos MySQL con tablas optimizadas. Creado a partir de fuentes oficiales: INE y CNIG (IGN)

## Datos

|Autonomías|19|
|:---|:---|
||17 comunidades de las que 2 son archipiélagos: las islas Baleares y las Canarias<br />2 ciudades autónomas en África: Ceuta y Melilla|

|Provincias|52|Islas|11|
|:---|:---|:---|:---|
||49 sin islas<br />3 con islas<br />&nbsp;|<br /><br /><br />|0<br />4 islas Baleares (1 provincia)<br />7 islas Canarias (en 2 provincias)|

|Municipios|8.131|
|:---|:---|

Códigos postales|11.053|Núcleos de población|> 29.000|
|:---|:---|:---|:---|

## Motivación
1. Disponer de una base de datos relacional en la que estén normalizados los datos de provincia y municipios españoles, usando IDs numéricos _compatibles_ con los oficiales y no números inventados con AUTOINCREMENT que es lo fácil.
2. Poder determinar a qué municipio oficial pertenece un código postal y cuántas calles comprende (aunque hay códigos postales que pertenecen a más de un municipio).
3. Obtener la geoposición (latitud/longitud) de los municipios y otra información, como isla, provincia, comunidad autónoma, nº de habitantes, altitud...
4. Poder presentarle al usuario los municipios de la ISLA a la que se refiere, en lugar de como hace "todo el mundo": darle los municipios de todas las islas mezclados, solo porque son "de la misma provincia"...

## Fuentes de datos
INE - Instituto Nacional de Estadística, que mantiene los datos de municipios / provincias / comunidades autónomas y el callejero del censo electoral con sus códigos postales.

CNIG - Centro Nacional de Información Geográfica, dirigido por el IGN (Instituto Geográfico Nacional), que mantiene diversos conjuntos de datos geográficos.

## Tablas
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

