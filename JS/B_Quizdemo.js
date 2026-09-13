$(function () {

    // Du lieu cau hoi demo (chua can goi API)
    var B_Quizdemo_DanhSachCauHoi = [
        {
            tu: "Airport",
            dapAn: ["Sân bay", "Bến xe", "Nhà ga", "Bến tàu"],
            dungTaiViTri: 0
        },
        {
            tu: "Software",
            dapAn: ["Phần cứng", "Phần mềm", "Mạng máy tính", "Ứng dụng di động"],
            dungTaiViTri: 1
        },
        {
            tu: "Passport",
            dapAn: ["Vé máy bay", "Hộ chiếu", "Visa", "Chứng minh thư"],
            dungTaiViTri: 1
        },
        {
            tu: "Noodle",
            dapAn: ["Mì", "Cơm", "Bánh mì", "Cháo"],
            dungTaiViTri: 0
        },
        {
            tu: "Meeting",
            dapAn: ["Buổi tiệc", "Kỳ nghỉ", "Cuộc họp", "Hội chợ"],
            dungTaiViTri: 2
        }
    ];

    var B_Quizdemo_ViTriCauHoi = 0;
    var B_Quizdemo_DaChonViTri = null;
    var B_Quizdemo_SoCauDung = 0;

    function B_Quizdemo_HienThiCauHoi(viTri) {
        var cau = B_Quizdemo_DanhSachCauHoi[viTri];
        var tongSoCau = B_Quizdemo_DanhSachCauHoi.length;

        $("#B_Quizdemo_TuVung").text(cau.tu);
        $("#B_Quizdemo_SoCauHoi").text("Câu " + (viTri + 1) + "/" + tongSoCau);
        $("#B_Quizdemo_ThanhTienDo").css("width", (((viTri + 1) / tongSoCau) * 100) + "%");

        var chuCai = ["A", "B", "C", "D"];
        var $hopDapAn = $("#B_Quizdemo_DapAn");
        $hopDapAn.empty();

        for (var i = 0; i < cau.dapAn.length; i++) {
            var $luaChon = $("<div></div>")
                .addClass("B_Quizdemo_LuaChon")
                .attr("data-vitri", i)
                .text(chuCai[i] + ". " + cau.dapAn[i]);

            $hopDapAn.append($luaChon);
        }

        B_Quizdemo_DaChonViTri = null;
        $("#B_Quizdemo_BtnCauTiep").prop("disabled", true);
    }

    function B_Quizdemo_KetThucDemo() {
        $(".B_Quizdemo_Banner").text(
            "Demo hoàn thành - bạn trả lời đúng " + B_Quizdemo_SoCauDung + "/" +
            B_Quizdemo_DanhSachCauHoi.length + " câu. Đăng ký để làm Quiz thật!"
        );
        $("#B_Quizdemo_TuVung").text("Hết câu hỏi demo");
        $("#B_Quizdemo_DapAn").empty();
        $("#B_Quizdemo_BtnCauTiep").prop("disabled", true);
    }

    // Chon 1 phuong an tra loi
    $("#B_Quizdemo_DapAn").on("click", ".B_Quizdemo_LuaChon", function () {
        $(".B_Quizdemo_LuaChon").removeClass("B_Quizdemo_DangChon");
        $(this).addClass("B_Quizdemo_DangChon");

        B_Quizdemo_DaChonViTri = parseInt($(this).attr("data-vitri"), 10);
        $("#B_Quizdemo_BtnCauTiep").prop("disabled", false);
    });

    // Nut Cau tiep
    $("#B_Quizdemo_BtnCauTiep").on("click", function () {
        var cauHienTai = B_Quizdemo_DanhSachCauHoi[B_Quizdemo_ViTriCauHoi];

        if (B_Quizdemo_DaChonViTri === cauHienTai.dungTaiViTri) {
            B_Quizdemo_SoCauDung++;
        }

        B_Quizdemo_ViTriCauHoi++;

        if (B_Quizdemo_ViTriCauHoi < B_Quizdemo_DanhSachCauHoi.length) {
            B_Quizdemo_HienThiCauHoi(B_Quizdemo_ViTriCauHoi);
        } else {
            B_Quizdemo_KetThucDemo();
        }
    });

    // Khoi tao cau hoi dau tien
    B_Quizdemo_HienThiCauHoi(B_Quizdemo_ViTriCauHoi);

});
