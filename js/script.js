function countdownMinutesFormat(v)
{
    var h = Math.floor(v / 60);
    var m = v % 60;

    while (h.length < 2)
        h = '0' + h;
    while (m.length < 2)
        m = '0' + m;

    return h + ':' + m;
}
