function trim(strText) {
    // this will get rid of leading spaces
    while (strText.substring(0, 1) === ' ')
        strText = strText.substring(1, strText.length);

    // this will get rid of trailing spaces
    while (strText.substring(strText.length - 1, strText.length) === ' ')
        strText = strText.substring(0, strText.length - 1);

    return strText;
}
