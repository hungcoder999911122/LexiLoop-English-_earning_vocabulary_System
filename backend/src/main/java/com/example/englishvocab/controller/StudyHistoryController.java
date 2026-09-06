package com.example.englishvocab.controller;

import com.example.englishvocab.dto.ApiDtos.SessionEnd;
import com.example.englishvocab.dto.ApiDtos.SessionStart;
import com.example.englishvocab.entity.StudyHistory;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.service.StudyHistoryService;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/sessions")
public class StudyHistoryController {
    private final StudyHistoryService studyHistoryService;

    public StudyHistoryController(StudyHistoryService studyHistoryService) {
        this.studyHistoryService = studyHistoryService;
    }

    @PostMapping
    public Map<String, Integer> start(@RequestBody SessionStart req, @AuthenticationPrincipal User user) {
        StudyHistory s = studyHistoryService.start(user, req);
        return Map.of("sessionId", s.getSessionID());
    }

    @PatchMapping("/{id}/end")
    public void end(@PathVariable Integer id, @RequestBody SessionEnd req, @AuthenticationPrincipal User user) {
        studyHistoryService.end(user, id, req);
    }

    @GetMapping
    public List<Map<String, Object>> mine(@AuthenticationPrincipal User user) {
        return studyHistoryService.mine(user).stream().map(s -> Map.<String, Object>of(
                "sessionId", s.getSessionID(),
                "mode", s.getStudyMode(),
                "topicName", s.getTopic() == null ? "" : s.getTopic().getTopicName(),
                "itemsStudied", s.getItemsStudied() == null ? 0 : s.getItemsStudied(),
                "correctCount", s.getCorrectCount() == null ? 0 : s.getCorrectCount(),
                "wrongCount", s.getWrongCount() == null ? 0 : s.getWrongCount()
        )).toList();
    }
}
