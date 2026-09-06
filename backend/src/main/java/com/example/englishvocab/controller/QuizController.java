package com.example.englishvocab.controller;

import com.example.englishvocab.dto.ApiDtos.*;
import com.example.englishvocab.entity.QuizResult;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.service.QuizService;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/quizzes")
public class QuizController {
    private final QuizService quizService;

    public QuizController(QuizService quizService) {
        this.quizService = quizService;
    }

    @PostMapping("/start")
    public QuizDto start(@RequestBody QuizStartRequest req, @AuthenticationPrincipal User user) {
        return quizService.start(user, req);
    }

    @PostMapping("/submit")
    public QuizResultDto submit(@RequestBody QuizSubmitRequest req, @AuthenticationPrincipal User user) {
        return quizService.submit(user, req);
    }

    @GetMapping("/history")
    public List<Map<String, Object>> history(@AuthenticationPrincipal User user) {
        List<QuizResult> list = quizService.history(user);
        return list.stream().map(r -> Map.<String, Object>of(
                "resultId", r.getResultID(),
                "title", r.getQuiz().getTitle(),
                "score", r.getScore(),
                "total", r.getTotal(),
                "percent", r.getTotal() == 0 ? 0 : Math.round(r.getScore() * 1000.0 / r.getTotal()) / 10.0,
                "takenAt", r.getTakenAt() == null ? "" : r.getTakenAt().toString()
        )).toList();
    }
}
